<?php

namespace App\Services;

use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\CapabilityProfile;
use Mike42\Escpos\EscposImage;

class PrinterService
{
    public function printReceipt($receipt_printer, $data)
    {
        $width = $receipt_printer->char_per_line;
        // inline formatter functions
        $line = function ($char = "-") use ($width) {
            return str_repeat($char, $width) . "\n";
        };

        $center = function ($text) use ($width) {
            $textLength = mb_strlen($text, 'UTF-8');
            if ($textLength >= $width) {
                return $text . "\n"; // too long, no centering
            }
            $padTotal = $width - $textLength;
            $padLeft = floor($padTotal / 2);
            $padRight = $padTotal - $padLeft;
            return str_repeat(" ", $padLeft) . $text . str_repeat(" ", $padRight) . "\n";
        };


        $leftRight = function ($left, $right) use ($width) {
            $left  = trim($left);
            $right = trim($right);
            $spaces = $width - mb_strlen($left, 'UTF-8') - mb_strlen($right, 'UTF-8');
            return $left . str_repeat(" ", max($spaces, 0)) . $right . "\n";
        };

        $wrapText = function ($label, $text) use ($width) {
            $full = $label . $text;
            $lines = [];
            while (mb_strlen($full, 'UTF-8') > $width) {
                $lines[] = mb_substr($full, 0, $width, 'UTF-8');
                $full = mb_substr($full, $width, null, 'UTF-8');
            }
            $lines[] = $full;
            return implode("\n", $lines) . "\n";
        };

        // build receipt text
        $text = '';
        $text .= $data['shop_name'] . "\n";
        $text .= __('db.Address').': '.$data['shop_address'] . "\n";
        $text .= __('db.Phone Number').': '.$data['shop_phone'] . "\n";
        $text .= $line();
        $text .= $leftRight(__('db.date').': ', $data['date']);
        if (isset($data['reference'])) {
            $text .= $leftRight(__('db.reference').': ', $data['reference']);
        }
        if (isset($data['customer'])) {
            $text .= $leftRight(__('db.customer').': ', $data['customer']);
        }
        if (isset($data['table'])) {
            $text .= $leftRight(__('db.table').': ', $data['table']);
        }
        if (isset($data['queue'])) {
            $text .= $leftRight(__('db.Queue').': ', $data['queue']);
        }
        if (isset($data['sale_custom_fields'])) {
            foreach ($data['sale_custom_fields'] as $field) {
                $text .= $leftRight($field['label'].': ', $field['value']);
            }
        }
        if (isset($data['customer_custom_fields'])) {
            foreach ($data['customer_custom_fields'] as $field) {
                $text .= $leftRight($field['label'].': ', $field['value']);
            }
        }
        $text .= $line();

        if (isset($data['items'])) {
            foreach ($data['items'] as $item) {
                $text .= $wrapText("", $item['name']); // wrap item name
                if (!empty($item['topping_names'])) {
                    $text .= $wrapText("", $item['topping_names']);
                }
                if (!empty($item['custom_fields'])) {
                    $text .= $wrapText("", $item['custom_fields']);
                }

                $text .= $leftRight($item['qtyline'].$item['tax_info'], $item['subtotal']);
            }
        }

        $text .= $line();
        $text .= $leftRight(__('db.Total').': ', $data['total']);
        if (isset($data['igst'])) {
            $text .= $leftRight('IGST: ', $data['igst']);
        }
        elseif (isset($data['sgstandcgst'])) {
            $text .= $leftRight('SGST: ', $data['sgstandcgst']);
            $text .= $leftRight('CGST: ', $data['sgstandcgst']);
        }
        if (isset($data['order_tax'])) {
            $text .= $leftRight(__('db.Order Tax').': ', $data['order_tax']);
        }
        if (isset($data['order_discount'])) {
            $text .= $leftRight(__('db.Order Discount').': ', $data['order_discount']);
        }
        if (isset($data['coupon_discount'])) {
            $text .= $leftRight(__('db.Coupon Discount').': ', $data['coupon_discount']);
        }
        if (isset($data['shipping_cost'])) {
            $text .= $leftRight(__('db.Shipping Cost').': ', $data['shipping_cost']);
        }
        $text .= $leftRight(__('db.grand total').': ', $data['grand_total']);
        if (isset($data['due'])) {
            $text .= $leftRight(__('db.Due').': ', $data['due']);
        }
        if (isset($data['total_due'])) {
            $text .= $leftRight(__('db.Total Due').': ', $data['total_due']);
        }

        $text .= $line();
        if (isset($data['amount_in_words'])) {
            $text .= $wrapText(__('db.In Words').': ', $data['amount_in_words']);
            $text .= $line();
        }

        if (isset($data['payments'])) {
            foreach ($data['payments'] as $payment) {
                $text .= $leftRight(__('db.Paid By').': ' . $payment['paid_by'] , __('db.Amount').': '. $payment['amount']);
                $text .= $leftRight(__('db.Change').': ', $payment['change']);
            }
        }
        $text .= $line();
        if (isset($data['served_by'])) {
            $text .= $wrapText(__('db.Served By').': ', $data['served_by']);
        }
        $text .= $line();
        if (isset($data['footer_text'])) {
            $text .= $center($data['footer_text']);
        }
        $text .= $line();

        $profile = CapabilityProfile::load($receipt_printer->capability_profile);
        $connector = $this->getConnector($receipt_printer);
        $printer = new Printer($connector, $profile);

        $printer->setJustification(Printer::JUSTIFY_CENTER);
        // if (isset($data['shop_logo'])) {
        //     $logo = EscposImage::load($data['shop_logo']);
        //     $printer->bitImage($logo);
        // }

        $printer->text($text . "\n");

        // if (isset($data['barcode'])) {
        //     $printer->barcode($data['barcode'], Printer::BARCODE_CODE128);
        // }
        // if (isset($data['qrcode'])) {
        //     $printer->qrCode($data['qrcode'], Printer::QR_ECLEVEL_L, 6, Printer::QR_MODEL_2);
        // }

        $printer->cut();
        $printer->close();
    }

    public function getConnector($receipt_printer)
    {
        switch ($receipt_printer->connection_type) {
            case 'windows':
                // Example: smb://PC_NAME/Printer_Share
                return new WindowsPrintConnector($receipt_printer->path);

            case 'linux':
                // Example: /dev/usb/lp0 or /dev/ttyUSB0
                return new FilePrintConnector($receipt_printer->path);

            case 'network':
                // Example: 192.168.0.87:9100
                return new NetworkPrintConnector($receipt_printer->ip_address, $receipt_printer->port);

            default:
                throw new \Exception("Unsupported connection type: {$receipt_printer->connection_type}");
        }
    }
}
