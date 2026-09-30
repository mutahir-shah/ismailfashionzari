<?php

namespace Modules\Repair\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Customer;
use App\Models\User;
use App\Models\Product;
use App\Models\Warehouse;
use Modules\Repair\Entities\DeviceType;
use Modules\Repair\Entities\ServiceDevice;
use Modules\Repair\Entities\ServiceJob;
use Modules\Repair\Entities\ServiceJobItem;
use Modules\Repair\Entities\ServiceJobUpdate;
use Modules\Repair\Entities\ServiceVehicle;

class ServiceJobSeeder extends Seeder
{
    public function run(): void
    {
        DB::beginTransaction();

        try {

            $customer   = Customer::where('is_active', true)->first();
            $warehouse  = Warehouse::where('is_active', true)->first();
            $technician = User::where('is_active', true)->first();
            $products   = Product::where('is_active', true)->take(5)->get();

            $deviceType  = DeviceType::where('category', 'device')->first();
            $vehicleType = DeviceType::where('category', 'vehicle')->first();

            for ($i = 1; $i <= 15; $i++) {

                $type = $i % 2 == 0 ? 'device' : 'vehicle';

                $job = ServiceJob::create([
                    'reference_no' => 'SRV-' . date('Ymd') . '-' . strtoupper(Str::random(5)),
                    'customer_id'  => $customer->id,
                    'service_type' => $type,
                    'title'        => $type == 'device'
                        ? 'Mobile Issue #' . $i
                        : 'Vehicle Problem #' . $i,
                    'description'  => 'Auto generated service job',
                    'status'       => ['pending', 'in_progress', 'completed'][rand(0,2)],
                    'priority'     => ['low', 'medium', 'high'][rand(0,2)],
                    'assigned_to'  => $technician->id,
                    'created_by'   => $technician->id,
                    'warehouse_id' => $warehouse->id,
                    'service_charge' => rand(100, 500),
                    'discount'       => rand(0, 100),
                    'tax'            => rand(0, 50),
                    'total_amount'   => 0,
                    'paid_amount'    => 0,
                    'due_amount'     => 0,
                ]);

                // ================= DEVICE =================
                if ($type == 'device') {
                    ServiceDevice::create([
                        'service_job_id' => $job->id,
                        'device_type'    => $deviceType->name ?? 'Mobile',
                        'brand'          => 'Samsung',
                        'model'          => 'Model ' . rand(1, 20),
                        'serial_number'  => 'SN' . rand(10000, 99999),
                        'imei'           => rand(100000000000000, 999999999999999),
                        'issue_reported' => 'Screen / battery issue',
                    ]);
                }

                // ================= VEHICLE =================
                if ($type == 'vehicle') {
                    ServiceVehicle::create([
                        'service_job_id' => $job->id,
                        'vehicle_type'   => $vehicleType->name ?? 'Bike',
                        'brand'          => 'Yamaha',
                        'model'          => 'R' . rand(10, 50),
                        'year'           => rand(2018, 2024),
                        'registration_no'=> 'REG-' . rand(1000, 9999),
                        'engine_no'      => 'ENG' . rand(1000, 9999),
                        'chassis_no'     => 'CHS' . rand(1000, 9999),
                        'mileage'        => rand(5000, 30000),
                        'fuel_level'     => 'Half',
                    ]);
                }

                // ================= PARTS =================
                foreach ($products as $product) {

                    $qty = rand(1, 3);

                    ServiceJobItem::create([
                        'service_job_id' => $job->id,
                        'product_id'     => $product->id,
                        'quantity'       => $qty,
                        'unit_price'     => $product->price,
                        'discount'       => 0,
                        'tax'            => 0,
                        'total'          => $product->price * $qty,
                    ]);
                }

                // ================= UPDATE =================
                ServiceJobUpdate::create([
                    'service_job_id' => $job->id,
                    'status'         => $job->status,
                    'note'           => 'Auto generated job #' . $i,
                    'updated_by'     => $technician->id,
                ]);

                // ================= TOTAL CALC =================
                $job->recalculateTotals();
            }

            DB::commit();

            $this->command->info('✅ 15 Service Jobs Created Successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            dd($e->getMessage());
        }
    }
}
