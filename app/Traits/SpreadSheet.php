<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;

trait SpreadSheet
{
    protected function getByName($spreadSheetId, $sheetName)
    {
        $link = "https://docs.google.com/spreadsheets/d/$spreadSheetId/gviz/tq?tqx=out:csv&sheet=".$sheetName;

        $response = Http::get($link)->body();
        $rows = array_map('str_getcsv', explode("\n", trim($response)));
        $headers = array_shift($rows);
        $uniqueHeaders = [];
        $lastPrefix = null;
        $multiWordPrefix = [
            'Le Minerale Galon' => 'Le Minerale',
        ];

        foreach ($headers as $h) {
            $baseKey = trim($h);
            if ($baseKey === '') {
                $baseKey = 'Col';
            }

            if (array_key_exists($baseKey, $multiWordPrefix)) {
                $lastPrefix = $multiWordPrefix[$baseKey];
                $newKey = $baseKey;
            } elseif (! preg_match('/^\d/', $baseKey)) {
                $parts = explode(' ', $baseKey);
                $lastPrefix = $parts[0];
                $newKey = $baseKey;
            } else {
                if ($lastPrefix) {
                    $newKey = $lastPrefix.' '.$baseKey;
                } else {
                    $newKey = $baseKey;
                }
            }

            $originalKey = $newKey;
            $i = 1;
            while (in_array($newKey, $uniqueHeaders)) {
                $newKey = $originalKey." ($i)";
                $i++;
            }

            $uniqueHeaders[] = $newKey;
        }
        $data = collect($rows)->map(function ($row) use ($uniqueHeaders) {
            $item = array_combine($uniqueHeaders, $row);
            unset($item['Col'], $item['Galon']);

            return $item;
        });
        $harga = $data->firstWhere('Nama Pelanggan', 'Harga Satuan');
        $data = $data->reject(function ($row) use (&$skipNext) {
            if ($skipNext) {
                $skipNext = false;

                return true;
            }

            if ($row['Nama Pelanggan'] === '' || $row['Nama Pelanggan'] === 'Harga Satuan' || $row['Nama Pelanggan'] === 'KE DEPOT') {
                $skipNext = ($row['Nama Pelanggan'] === 'Harga Satuan');

                return true;
            }

            return false;
        })->values();
        $data = $data->map(function ($row) {
            return collect($row)->map(function ($value) {
                return $value === '' ? '0' : $value;
            })->all();
        });

        foreach ($data as $id => $dataRow) {
            $result = [
                'name' => $dataRow['Nama Pelanggan'],
                'phone' => $dataRow['Telepon'] ?? '',
                'date' => $sheetName.'-'.now()->year,
                'total_price' => 0,
                'total_count' => 0,
                'products' => [],
            ];

            foreach ($dataRow as $key => $qty) {
                if (in_array($key, ['Nama Pelanggan', 'Telepon'])) {
                    continue;
                } // skip nama
                $quantity = (int) $qty;
                if ($quantity > 0) {
                    // code...
                    $price = isset($harga[$key]) ? (int) str_replace(',', '', $harga[$key]) : 0;
                    $total = $quantity * $price;

                    $result['total_count'] += $quantity;
                    $result['total_price'] += $total;

                    $result['products'][] = [
                        'name' => $key,
                        'quantity' => $quantity,
                        'price' => $price,
                        'total' => $total,
                    ];
                }
            }
            $result['products'] = json_encode($result['products']);
            $data[$id] = $result;
        }

        return $data->toArray();
    }

    protected function getSheetName($spreadSheetId, $subDay)
    {
        $now = now()->subDays($subDay)->toDateString();
        $splitted = explode('-', $now);
        $usedSheet = $splitted[2].'-'.$splitted[1];
        $apiKey = env('SHEETS_KEY');
        $link = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadSheetId}?fields=sheets.properties.title&key={$apiKey}";
        $response = Http::get($link)->body();
        $data = json_decode($response)->sheets;
        $titles = array_map(fn ($s) => $s->properties->title, $data);
        $bulan = [
            'januari' => '01',
            'februari' => '02',
            'maret' => '03',
            'april' => '04',
            'mei' => '05',
            'juni' => '06',
            'juli' => '07',
            'agustus' => '08',
            'september' => '09',
            'oktober' => '10',
            'november' => '11',
            'desember' => '12',
        ];
        $mapped = [];

        foreach ($titles as $title) {
            $lower = mb_strtolower($title);
            foreach ($bulan as $name => $num) {
                if (str_contains($lower, $name)) {
                    if (preg_match('/\d{1,2}/', $lower, $match)) {
                        $day = str_pad($match[0], 2, '0', STR_PAD_LEFT);
                        $key = "{$day}-{$num}";
                        $mapped[$key] = $title;
                    }
                    break;
                }
            }
        }

        return $mapped[$usedSheet];
    }
}
