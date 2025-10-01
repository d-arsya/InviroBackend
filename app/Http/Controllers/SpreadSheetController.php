<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponder;
use App\Traits\SpreadSheet;
use Dedoc\Scramble\Attributes\Group;

class SpreadSheetController extends Controller
{
    use ApiResponder, SpreadSheet;

    /**
     * Test
     *
     * Mendapatkan data penjualan dengan input spreadsheet ID dan hari kebelakang
     */
    #[Group('Spreadsheet')]
    public function test(string $id, int $day)
    {
        $sheetName = $this->getSheetName($id, $day);
        $data = $this->getByName($id, $sheetName);

        return $this->success($data);
    }
}
