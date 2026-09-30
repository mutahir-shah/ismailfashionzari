<?php

namespace App\Services\Demo;

use App\Models\Account;
use App\Models\CashRegister;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Product_Warehouse;
use App\Services\Domain\RepairDomainService;
use Illuminate\Support\Facades\DB;
use Modules\Repair\Entities\ServiceJob;
use RuntimeException;

class GoldenDemoPhase15Service
{
    private const REFS = ['DEMO-REP-001','DEMO-REP-002','DEMO-REP-003','DEMO-REP-004','DEMO-REP-005','DEMO-REP-006'];
    private const REQUIRED_PART_STOCK = 3;

    public function __construct(
        private RepairDomainService $repairs,
        private GoldenDemoValidator $validator,
        private GoldenDemoManifestService $manifestService
    ) {}

    public function build(bool $force = false): array
    {
        GoldenDemoSafety::assertExactDemoDatabase();
        $manifest = $this->manifest();
        if (isset($manifest['phase15'])) {
            $this->assertCheckpointHash($manifest);
            if (($manifest['phase15_validation']['passed'] ?? false) === true) {
                return $this->validate($manifest['phase14_checkpoint'], $manifest['phase15']);
            }
        } else {
            $phase14 = $this->validator->validatePhase14($manifest['phase13_checkpoint'] ?? [], $manifest['phase14'] ?? []);
            if (($phase14['ready_for_repair_phase'] ?? 'NO') !== 'YES') throw new RuntimeException('Phase 14 readiness is required before Phase 15.');
        }
        if (!isset($manifest['phase14_checkpoint'])) {
            $manifest['phase14_checkpoint'] = $this->checkpoint();
            $manifest['phase14_checkpoint_hash'] = $this->hash($manifest['phase14_checkpoint']);
            $this->write($manifest);
        }
        $this->assertCheckpointHash($manifest);
        if (!isset($manifest['phase15'])) {
            $manifest['phase15'] = $this->initialize();
            $this->write($manifest);
        }

        foreach ($this->definitions($manifest['phase15']) as $definition) {
            $ref = $definition['job']['reference_no'];
            if (isset($manifest['phase15']['scenarios'][$ref])) continue;
            $manifest['phase15']['scenarios'][$ref] = $this->execute($definition, $manifest['phase15']);
            $this->write($manifest);
        }

        $result = $this->validate($manifest['phase14_checkpoint'], $manifest['phase15']);
        if (!$result['passed']) throw new RuntimeException('PHASE 15 VALIDATION FAILED: '.json_encode($result));
        $manifest['phase15_validation'] = $result;
        $manifest['phases_completed'] = array_values(array_unique(array_merge($manifest['phases_completed'] ?? [], ['Phase 15'])));
        $this->write($manifest);
        return $result;
    }

    public function validate(array $checkpoint, array $state): array
    {
        $this->assertStateReferences($state);
        $expectedStock = $checkpoint['stock_tuple_map'];
        foreach ($state['expected_stock_movements'] as $tuple => $qty) $expectedStock[$tuple] = (float) $expectedStock[$tuple] - (float) $qty;
        $actualStock = $this->tupleMap();
        $stockVariance = [];
        foreach (array_unique(array_merge(array_keys($expectedStock), array_keys($actualStock))) as $tuple) {
            $v = (float) ($actualStock[$tuple] ?? 0) - (float) ($expectedStock[$tuple] ?? 0);
            if (abs($v) >= .0001) $stockVariance[$tuple] = $v;
        }

        $baseline = $this->validator->recordBaseline();
        $accounts = collect($baseline['financial']['payment_account_balances'])->keyBy('id')->map(fn ($a) => (float) $a['balance'])->all();
        $accountVariance = [];
        foreach ($checkpoint['payment_account_balances'] as $id => $starting) {
            $expected = (float) $starting + (float) ($state['expected_account_deltas'][$id] ?? 0);
            $v = (float) ($accounts[$id] ?? 0) - $expected;
            if (abs($v) >= .0001) $accountVariance[$id] = $v;
        }
        $audit = $this->validator->auditJournalIntegrity();
        $repairJournals = JournalEntry::where(function ($q) {
            $q->whereIn('source_id', DB::table('sales')->whereIn('reference_no', self::REFS)->select('id'))
              ->where('source_type', \App\Models\Sale::class);
        })->orWhere(function ($q) {
            $q->whereIn('source_id', DB::table('payments')->whereIn('payment_reference', collect(self::REFS)->map(fn ($r) => $r.'-PAY'))->select('id'))
              ->where('source_type', \App\Models\Payment::class);
        })->get();
        // DEMO-REP-005's payment is physically removed by canonical cancellation;
        // its source IDs are retained in the immutable scenario evidence.
        $cancelPaymentId = $state['scenarios']['DEMO-REP-005']['payment_id'] ?? 0;
        if ($cancelPaymentId) $repairJournals = $repairJournals->merge(JournalEntry::where('source_type', \App\Models\Payment::class)->where('source_id', $cancelPaymentId)->get())->unique('id');
        $duplicate = $repairJournals->groupBy(fn ($j) => $j->source_type.':'.$j->source_id.':'.$j->event_type)->filter(fn ($g) => $g->count() > 1)->count();
        $unbalancedRepair = $repairJournals->filter(fn ($j) => abs((float) $j->lines()->sum('debit') - (float) $j->lines()->sum('credit')) >= .01)->count();
        $arExpected = (float) $checkpoint['customer_ar'] + (float) $state['expected_customer_ar_delta'];
        $glArExpected = (float) $checkpoint['gl_ar'] + (float) $state['expected_customer_ar_delta'];
        $actualRegister = (float) $checkpoint['cash_register'] + (float) DB::table('payments')->whereIn('service_job_id', $state['persisted_job_ids'])->whereNotNull('cash_register_id')->sum('amount');
        $expectedRegister = (float) $checkpoint['cash_register'] + (float) $state['expected_register_delta'];

        $result = [
            'passed' => false,
            'phase14_checkpoint_hash' => $this->hash($checkpoint),
            'repairs' => ['persisted' => ServiceJob::whereIn('reference_no', self::REFS)->count(), 'active' => ServiceJob::where('reference_no','DEMO-REP-002')->where('status','in_progress')->count(), 'service_only_supported' => true],
            'stock' => ['variance' => $stockVariance, 'expected_consumption' => array_sum($state['expected_stock_movements']), 'reconciled' => !$stockVariance],
            'customer_ar' => ['phase14' => $checkpoint['customer_ar'], 'delta' => $state['expected_customer_ar_delta'], 'expected' => $arExpected, 'operational' => (float) $baseline['financial']['customer_operational_dues'], 'statement' => $this->customerStatementTotal(), 'gl' => (float) $baseline['accounting']['gl_ar_balance']],
            'supplier_ap' => ['phase14' => $checkpoint['supplier_ap'], 'delta' => 0, 'operational' => (float) $baseline['financial']['supplier_operational_dues'], 'gl' => (float) $baseline['accounting']['gl_ap_balance']],
            'payment_accounts' => ['expected_deltas' => $state['expected_account_deltas'], 'variance' => $accountVariance],
            'cash_register' => ['expected' => $expectedRegister, 'actual' => $actualRegister, 'variance' => $actualRegister - $expectedRegister],
            'accounting' => ['repair_journals' => $repairJournals->count(), 'expected_journals' => 11, 'actual_total' => $audit['journals_count'], 'journal_lines' => JournalLine::count(), 'debit' => (float) JournalLine::sum('debit'), 'credit' => (float) JournalLine::sum('credit'), 'unbalanced' => $audit['unbalanced_journals_count'], 'repair_unbalanced' => $unbalancedRepair, 'duplicates' => $duplicate, 'source_integrity_failures' => $audit['source_integrity_failures_count']],
            'cancellation' => $state['scenarios']['DEMO-REP-005'],
        ];
        $result['customer_ar']['variances'] = ['operational' => $result['customer_ar']['operational'] - $arExpected, 'statement' => $result['customer_ar']['statement'] - $arExpected, 'gl' => $result['customer_ar']['gl'] - $glArExpected];
        $result['supplier_ap']['variances'] = ['operational' => $result['supplier_ap']['operational'] - $checkpoint['supplier_ap'], 'gl' => $result['supplier_ap']['gl'] - $checkpoint['gl_ap']];
        $result['passed'] = !$stockVariance && !$accountVariance && abs($result['cash_register']['variance']) < .0001
            && collect($result['customer_ar']['variances'])->every(fn ($v) => abs($v) < .0001)
            && collect($result['supplier_ap']['variances'])->every(fn ($v) => abs($v) < .0001)
            && $repairJournals->count() === 11 && $duplicate === 0 && $unbalancedRepair === 0
            && $audit['unbalanced_journals_count'] === 0 && $audit['source_integrity_failures_count'] === 0;
        $result['ready_for_cash_register_closing_phase'] = $result['passed'] ? 'YES' : 'NO';
        return $result;
    }

    private function checkpoint(): array
    {
        $base = $this->validator->recordBaseline();
        $audit = $this->validator->auditJournalIntegrity();
        return ['captured_at' => now()->toIso8601String(), 'stock_tuple_map' => $this->tupleMap(),
            'company_stock' => (float) DB::table('products')->sum('qty'), 'service_center_stock' => (float) DB::table('product_warehouse')->where('warehouse_id', 5)->sum('qty'),
            'customer_ar' => (float) $base['financial']['customer_operational_dues'], 'supplier_ap' => (float) $base['financial']['supplier_operational_dues'],
            'gl_ar' => (float) $base['accounting']['gl_ar_balance'], 'gl_ap' => (float) $base['accounting']['gl_ap_balance'],
            'payment_account_balances' => collect($base['financial']['payment_account_balances'])->keyBy('id')->map(fn ($a) => (float) $a['balance'])->all(),
            'cash_register' => (float) (CashRegister::where('status', true)->value('cash_in_hand') ?? 0),
            'journal_count' => $audit['journals_count'], 'journal_line_count' => JournalLine::count(), 'gl_debit' => (float) JournalLine::sum('debit'), 'gl_credit' => (float) JournalLine::sum('credit'),
            'source_integrity_failures' => $audit['source_integrity_failures_count'], 'repair_counts' => ['jobs' => ServiceJob::count(), 'sales' => DB::table('sales')->whereNotNull('repair_id')->count(), 'payments' => DB::table('payments')->whereNotNull('service_job_id')->count()]];
    }

    private function initialize(): array
    {
        $warehouse = DB::table('warehouses')->where('name', 'Service Center')->first();
        $customers = DB::table('customers')->whereIn('name', ['Ian Service Device Client','Jack Tech Repair Client'])->orderBy('id')->get();
        // The most-demanded retained part is consumed three times by the
        // deterministic Repair definitions. Requiring ten units tied the
        // phase to the reduced legacy catalog rather than its real demand.
        $parts = DB::table('products as p')->join('product_warehouse as pw','pw.product_id','=','p.id')->where('pw.warehouse_id',$warehouse->id)->whereNull('pw.variant_id')->where('pw.qty','>=',self::REQUIRED_PART_STOCK)->where('p.is_active',1)
            ->where(fn ($q) => $q->where('p.is_variant', 0)->orWhereNull('p.is_variant'))
            ->orderBy('p.code')->select('p.id','p.code','p.price','p.cost','pw.qty')->take(3)->get();
        if (!$warehouse || $customers->count() < 2 || $parts->count() < 3) throw new RuntimeException('Deterministic Repair masters/parts are unavailable.');
        $accounts = Account::whereIn('name',['Cash Account','Bank Account'])->pluck('id','name');
        return ['user_id' => (int) DB::table('users')->where('role_id',1)->value('id'), 'warehouse_id' => (int) $warehouse->id,
            'customer_ids' => $customers->pluck('id')->map(fn ($v)=>(int)$v)->all(), 'parts' => $parts->map(fn ($p)=>(array)$p)->all(),
            'cash_account_id' => (int) $accounts['Cash Account'], 'bank_account_id' => (int) $accounts['Bank Account'],
            'scenarios' => [], 'persisted_job_ids' => [], 'expected_stock_movements' => [], 'expected_customer_ar_delta' => 600,
            'expected_account_deltas' => [(int)$accounts['Cash Account'] => 100, (int)$accounts['Bank Account'] => 524], 'expected_register_delta' => 100];
    }

    private function definitions(array $s): array
    {
        [$p1,$p2,$p3]=$s['parts']; [$c1,$c2]=$s['customer_ids']; $w=$s['warehouse_id'];
        $base=fn($ref,$customer,$status)=>['reference_no'=>$ref,'customer_id'=>$customer,'warehouse_id'=>$w,'service_type'=>'device','title'=>$ref,'description'=>'Golden Demo controlled Repair','status'=>$status,'priority'=>'medium','device_type'=>'Phone','device_brand'=>'Golden Demo','device_model'=>$ref,'device_issue_reported'=>'Controlled certification issue'];
        return [
            ['job'=>$base('DEMO-REP-001',$c1,'completed'),'parts'=>[['product_id'=>$p1['id'],'quantity'=>1,'unit_price'=>299]],'charges'=>['service_charge'=>100],'payment'=>['amount'=>399,'paying_method'=>'Bank','account_id'=>$s['bank_account_id']]],
            ['job'=>$base('DEMO-REP-002',$c2,'in_progress'),'parts'=>[],'charges'=>[]],
            ['job'=>$base('DEMO-REP-003',$c1,'completed'),'parts'=>[['product_id'=>$p2['id'],'quantity'=>1,'unit_price'=>200]],'charges'=>['service_charge'=>100],'payment'=>['amount'=>100,'paying_method'=>'Cash','account_id'=>$s['cash_account_id']]],
            ['job'=>$base('DEMO-REP-004',$c2,'completed'),'parts'=>[['product_id'=>$p3['id'],'quantity'=>1,'unit_price'=>150],['product_id'=>$p1['id'],'quantity'=>2,'unit_price'=>100]],'charges'=>['service_charge'=>50]],
            ['job'=>$base('DEMO-REP-005',$c1,'completed'),'parts'=>[['product_id'=>$p2['id'],'quantity'=>1,'unit_price'=>100]],'charges'=>['service_charge'=>50],'payment'=>['amount'=>150,'paying_method'=>'Bank','account_id'=>$s['bank_account_id']],'cancel'=>true],
            ['job'=>$base('DEMO-REP-006',$c2,'completed'),'parts'=>[],'charges'=>['service_charge'=>125],'payment'=>['amount'=>125,'paying_method'=>'Bank','account_id'=>$s['bank_account_id']]],
        ];
    }

    private function execute(array $d, array &$state): array
    {
        return DB::transaction(function () use ($d, &$state) {
            $ref=$d['job']['reference_no']; $job=ServiceJob::where('reference_no',$ref)->first();
            if (!$job) $job=$this->repairs->create($d['job'],$state['user_id']);
            if (($d['parts'] || $d['charges']) && !$job->sale) $job=$this->repairs->configure($job,$d['parts'],$d['charges']);
            $payment=null;
            if (isset($d['payment'])) {
                $payment=$job->payments()->where('payment_reference',$ref.'-PAY')->first();
                if (!$payment) $payment=$this->repairs->addPayment($job,array_merge($d['payment'],['payment_reference'=>$ref.'-PAY','payment_note'=>'Golden Demo Phase 15']),$state['user_id']);
            }
            $record=['job_id'=>$job->id,'status'=>$job->status,'sale_id'=>$job->sale?->id,'payment_id'=>$payment?->id,'cancelled'=>false];
            if (!($d['cancel'] ?? false)) {
                $state['persisted_job_ids'][]=$job->id;
                foreach ($d['parts'] as $part) { $tuple=$part['product_id'].':0:'.$state['warehouse_id']; $state['expected_stock_movements'][$tuple]=($state['expected_stock_movements'][$tuple]??0)+(float)$part['quantity']; }
            } else {
                $before=$this->tupleMap(); $this->repairs->cancel($job); $after=$this->tupleMap();
                $record['cancelled']=true; $record['job_absent']=!ServiceJob::whereKey($job->id)->exists(); $record['stock_restored']=$before!==$after;
                $record['sale_reversed']=(bool) \App\Models\Sale::withTrashed()->whereKey($record['sale_id'])->whereNotNull('deleted_at')->exists();
                $record['payment_absent']=!DB::table('payments')->where('id',$record['payment_id'])->exists();
            }
            return $record;
        });
    }

    private function assertStateReferences(array $state): void
    {
        foreach (self::REFS as $ref) if (!isset($state['scenarios'][$ref])) throw new RuntimeException("Missing Phase 15 state for {$ref}.");
        foreach (array_slice(self::REFS,0,4) as $ref) if (!ServiceJob::where('reference_no',$ref)->exists()) throw new RuntimeException("Missing Repair {$ref}.");
        if (!ServiceJob::where('reference_no','DEMO-REP-006')->exists()) throw new RuntimeException('Missing service-only Repair.');
        if (ServiceJob::where('reference_no','DEMO-REP-005')->exists()) throw new RuntimeException('Cancelled Repair remains active.');
    }

    private function customerStatementTotal(): float
    {
        return (float) DB::table('customers')->where('is_active',1)->get()->sum(function($c){
            $sales=(float)DB::table('sales')->where('customer_id',$c->id)->whereNull('deleted_at')->sum('grand_total');
            $payments=(float)DB::table('payments')->join('sales','sales.id','=','payments.sale_id')->where('sales.customer_id',$c->id)->whereNull('sales.deleted_at')->whereNull('payments.return_id')->sum('payments.amount');
            $returns=(float)DB::table('returns')->where('customer_id',$c->id)->sum('grand_total');
            return (float)($c->opening_balance??0)+$sales-$payments-$returns;
        });
    }
    private function tupleMap(): array { return DB::table('product_warehouse')->get()->mapWithKeys(fn($r)=>[$r->product_id.':'.($r->variant_id?:0).':'.$r->warehouse_id=>(float)$r->qty])->all(); }
    private function hash(array $v): string { return hash('sha256',json_encode($v,JSON_UNESCAPED_SLASHES)); }
    private function assertCheckpointHash(array $m): void { if ($this->hash($m['phase14_checkpoint'])!==($m['phase14_checkpoint_hash']??null)) throw new RuntimeException('Immutable Phase 14 checkpoint drift detected.'); }
    private function manifest(): array { return $this->manifestService->read(); }
    private function write(array $m): void { $this->manifestService->merge($m); }
}
