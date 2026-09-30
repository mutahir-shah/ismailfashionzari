<?php

namespace App\Services\Demo;

use App\Models\CashRegister;
use App\Models\Expense;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Payment;
use App\Services\Domain\CashRegisterDomainService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GoldenDemoPhase16Service
{
    public function __construct(
        private CashRegisterDomainService $registers,
        private GoldenDemoValidator $validator,
        private GoldenDemoManifestService $manifestService
    ) {}

    public function build(bool $force = false): array
    {
        GoldenDemoSafety::assertExactDemoDatabase();
        $manifest = $this->manifest();
        if (isset($manifest['phase16'])) {
            $this->assertCheckpointHash($manifest);
            $result = $this->validate($manifest['phase15_checkpoint'], $manifest['phase16']);
            if (($manifest['phase16_validation']['passed'] ?? false) === true || $result['passed']) return $result;
            throw new RuntimeException('Existing Phase 16 state is not certifiable: '.json_encode($result));
        }
        if (($manifest['phase15_validation']['ready_for_cash_register_closing_phase'] ?? 'NO') !== 'YES') {
            throw new RuntimeException('Phase 15 readiness is required before Phase 16.');
        }
        if (!isset($manifest['phase15_checkpoint'])) {
            $manifest['phase15_checkpoint'] = $this->checkpoint();
            $manifest['phase15_checkpoint_hash'] = $this->hash($manifest['phase15_checkpoint']);
            $this->write($manifest);
        }
        $this->assertCheckpointHash($manifest);
        $checkpoint = $manifest['phase15_checkpoint'];
        $register = CashRegister::withoutGlobalScopes()->findOrFail($checkpoint['register']['id']);
        $expected = (float) $checkpoint['register']['reconstruction']['expected'];
        $closed = $this->registers->close($register, $expected, $expected);
        $state = [
            'reference_no' => 'DEMO-REG-CLOSE-001', 'register_id' => $closed->id,
            'expected_cash' => $expected, 'counted_cash' => (float) $closed->actual_cash,
            'variance' => $this->registers->variance($closed), 'closed_updated_at' => $closed->updated_at->toIso8601String(),
        ];
        $manifest['phase16'] = $state;
        $result = $this->validate($checkpoint, $state);
        if (!$result['passed']) throw new RuntimeException('PHASE 16 VALIDATION FAILED: '.json_encode($result));
        $manifest['phase16_validation'] = $result;
        $manifest['phases_completed'] = array_values(array_unique(array_merge($manifest['phases_completed'] ?? [], ['Phase 16'])));
        $this->write($manifest);
        return $result;
    }

    public function validate(array $checkpoint, array $state): array
    {
        $register = CashRegister::withoutGlobalScopes()->find($state['register_id'] ?? 0);
        $trace = $register ? $this->reconstruct($register) : ['expected' => NAN, 'rows' => [], 'aggregates' => []];
        $base = $this->validator->recordBaseline();
        $currentAccounts = collect($base['financial']['payment_account_balances'])->keyBy('id')->map(fn ($a)=>(float)$a['balance'])->all();
        $accountVariance = [];
        foreach ($checkpoint['payment_account_balances'] as $id=>$amount) {
            $v=(float)($currentAccounts[$id]??0)-(float)$amount; if(abs($v)>=.0001)$accountVariance[$id]=$v;
        }
        $actualStock = $this->tupleMap(); $stockVariance=[];
        foreach(array_unique(array_merge(array_keys($checkpoint['stock_tuple_map']),array_keys($actualStock))) as $tuple){
            $v=(float)($actualStock[$tuple]??0)-(float)($checkpoint['stock_tuple_map'][$tuple]??0); if(abs($v)>=.0001)$stockVariance[$tuple]=$v;
        }
        $audit=$this->validator->auditJournalIntegrity();
        $closingJournalDelta=$audit['journals_count']-(int)$checkpoint['accounting']['journal_count'];
        $result=[
            'passed'=>false,
            'register'=>[
                'reference_no'=>$state['reference_no']??null,'id'=>$register?->id,'status'=>$register?->status?'open':'closed',
                'opening_float'=>(float)($register?->cash_in_hand??0),'expected'=>(float)($state['expected_cash']??NAN),
                'reconstructed'=>(float)$trace['expected'],'counted'=>(float)($register?->actual_cash??NAN),
                'closing_balance'=>(float)($register?->closing_balance??NAN),
                'variance'=>$register?$this->registers->variance($register):NAN,'closed_at'=>$register?->updated_at?->toIso8601String(),
                'trace'=>$trace['rows'],'aggregates'=>$trace['aggregates'],
            ],
            'financial'=>[
                'customer_ar_variance'=>(float)$base['financial']['customer_operational_dues']-(float)$checkpoint['customer_ar'],
                'supplier_ap_variance'=>(float)$base['financial']['supplier_operational_dues']-(float)$checkpoint['supplier_ap'],
                'payment_account_variance'=>$accountVariance,'stock_variance'=>$stockVariance,
            ],
            'accounting'=>[
                'closing_journals_expected'=>0,'closing_journals_actual'=>$closingJournalDelta,
                'journal_count'=>$audit['journals_count'],'journal_lines'=>JournalLine::count(),
                'debit_delta'=>(float)JournalLine::sum('debit')-(float)$checkpoint['accounting']['gl_debit'],
                'credit_delta'=>(float)JournalLine::sum('credit')-(float)$checkpoint['accounting']['gl_credit'],
                'unbalanced'=>$audit['unbalanced_journals_count'],'source_integrity_failures'=>$audit['source_integrity_failures_count'],
            ],
        ];
        $registerClean=$register && !$register->status && abs((float)$register->closing_balance-(float)$trace['expected'])<.0001
            && abs((float)$register->actual_cash-(float)$trace['expected'])<.0001 && abs((float)$result['register']['variance'])<.0001;
        $financialClean=abs($result['financial']['customer_ar_variance'])<.0001 && abs($result['financial']['supplier_ap_variance'])<.0001 && !$accountVariance && !$stockVariance;
        $accountingClean=$closingJournalDelta===0 && abs($result['accounting']['debit_delta'])<.0001 && abs($result['accounting']['credit_delta'])<.0001
            && $audit['unbalanced_journals_count']===0 && $audit['source_integrity_failures_count']===0;
        $result['passed']=$registerClean && $financialClean && $accountingClean;
        $result['ready_for_final_certification_phase']=$result['passed']?'YES':'NO';
        return $result;
    }

    public function reconstruct(CashRegister $register): array
    {
        $rows=[]; $add=function(string $type,string $reference,float $amount)use(&$rows){$rows[]=['source_type'=>$type,'reference'=>$reference,'amount'=>round($amount,4)];};
        $saleReceipts=Payment::query()->join('sales','sales.id','=','payments.sale_id')
            ->where('payments.cash_register_id',$register->id)->whereNotNull('payments.sale_id')->whereNull('payments.return_id')->whereNull('sales.deleted_at')
            ->select('payments.*','sales.reference_no as source_reference','sales.sale_type')->orderBy('payments.id')->get();
        foreach($saleReceipts as $p)$add('sale_receipt',$p->source_reference,(float)$p->amount/((float)$p->exchange_rate?:1));
        $supplierRefunds=Payment::query()->join('purchases','purchases.id','=','payments.purchase_id')
            ->where('payments.cash_register_id',$register->id)->whereNotNull('payments.purchase_id')->whereNull('purchases.deleted_at')
            ->where(fn($q)=>$q->whereNotNull('payments.return_id')->orWhereNotNull('payments.purchase_return_id'))
            ->select('payments.*','purchases.reference_no as source_reference')->orderBy('payments.id')->get();
        foreach($supplierRefunds as $p)$add('supplier_refund',$p->source_reference,(float)$p->amount/((float)$p->exchange_rate?:1));
        $customerRefunds=Payment::query()->join('sales','sales.id','=','payments.sale_id')
            ->where('payments.cash_register_id',$register->id)->whereNotNull('payments.return_id')->whereNull('sales.deleted_at')
            ->select('payments.*','sales.reference_no as source_reference')->orderBy('payments.id')->get();
        foreach($customerRefunds as $p)$add('customer_refund',$p->source_reference,-((float)$p->amount/((float)$p->exchange_rate?:1)));
        $purchasePayments=Payment::query()->join('purchases','purchases.id','=','payments.purchase_id')
            ->where('payments.cash_register_id',$register->id)->whereNull('payments.return_id')->whereNull('payments.purchase_return_id')->whereNull('purchases.deleted_at')
            ->select('payments.*','purchases.reference_no as source_reference')->orderBy('payments.id')->get();
        foreach($purchasePayments as $p)$add('purchase_payment',$p->source_reference,-((float)$p->amount/((float)$p->exchange_rate?:1)));
        foreach(Expense::where('cash_register_id',$register->id)->orderBy('id')->get() as $e)$add('expense',$e->reference_no,-(float)$e->amount);
        $opening=(float)$register->cash_in_hand; $movement=(float)collect($rows)->sum('amount');
        $cashReceipts=(float)$saleReceipts->filter(fn($p)=>strcasecmp((string)$p->paying_method,'Cash')===0)->sum(fn($p)=>(float)$p->amount/((float)$p->exchange_rate?:1));
        $cashRefunds=(float)$customerRefunds->filter(fn($p)=>strcasecmp((string)$p->paying_method,'Cash')===0)->sum(fn($p)=>(float)$p->amount/((float)$p->exchange_rate?:1));
        return ['opening_float'=>$opening,'rows'=>$rows,'expected'=>round($opening+$movement,4),'aggregates'=>[
            'sale_receipts'=>(float)$saleReceipts->sum(fn($p)=>(float)$p->amount/((float)$p->exchange_rate?:1)),
            'supplier_refunds'=>(float)$supplierRefunds->sum(fn($p)=>(float)$p->amount/((float)$p->exchange_rate?:1)),
            'customer_refunds'=>(float)$customerRefunds->sum(fn($p)=>(float)$p->amount/((float)$p->exchange_rate?:1)),
            'purchase_payments'=>(float)$purchasePayments->sum(fn($p)=>(float)$p->amount/((float)$p->exchange_rate?:1)),
            'expenses'=>(float)Expense::where('cash_register_id',$register->id)->sum('amount'),
            'cash_method_receipts'=>$cashReceipts,'cash_method_refunds'=>$cashRefunds,'cash_only_drawer'=>round($opening+$cashReceipts-$cashRefunds,4),
        ]];
    }

    private function checkpoint(): array
    {
        $register=CashRegister::withoutGlobalScopes()->where('status',true)->orderBy('id')->sole();
        $base=$this->validator->recordBaseline(); $audit=$this->validator->auditJournalIntegrity();
        return ['captured_at'=>now()->toIso8601String(),'register'=>[
            'id'=>$register->id,'user_id'=>$register->user_id,'warehouse_id'=>$register->warehouse_id,
            'opening_amount'=>(float)$register->cash_in_hand,'stored_cash_in_hand'=>(float)$register->cash_in_hand,
            'status'=>(bool)$register->status,'opened_at'=>$register->created_at->toIso8601String(),'reconstruction'=>$this->reconstruct($register),
        ],'customer_ar'=>(float)$base['financial']['customer_operational_dues'],'supplier_ap'=>(float)$base['financial']['supplier_operational_dues'],
            'stock_tuple_map'=>$this->tupleMap(),'payment_account_balances'=>collect($base['financial']['payment_account_balances'])->keyBy('id')->map(fn($a)=>(float)$a['balance'])->all(),
            'accounting'=>['journal_count'=>$audit['journals_count'],'journal_line_count'=>JournalLine::count(),'gl_debit'=>(float)JournalLine::sum('debit'),'gl_credit'=>(float)JournalLine::sum('credit'),'unbalanced'=>$audit['unbalanced_journals_count'],'source_integrity_failures'=>$audit['source_integrity_failures_count']]];
    }
    private function tupleMap():array{return DB::table('product_warehouse')->get()->mapWithKeys(fn($r)=>[$r->product_id.':'.($r->variant_id?:0).':'.$r->warehouse_id=>(float)$r->qty])->all();}
    private function hash(array $v):string{return hash('sha256',json_encode($v,JSON_UNESCAPED_SLASHES));}
    private function assertCheckpointHash(array $m):void{if($this->hash($m['phase15_checkpoint'])!==($m['phase15_checkpoint_hash']??null))throw new RuntimeException('Immutable Phase 15 checkpoint drift detected.');}
    private function manifest(): array { return $this->manifestService->read(); }
    private function write(array $m): void { $this->manifestService->merge($m); }
}
