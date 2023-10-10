<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\JsonResponse;

class ExcelController extends AbstractController
{
    #[Route('/excel')]
    #[Route('/excel/index')]
    public function index(Request $request): Response
    {
        return $this->render('filters.html.twig', [
                'filters' => $this->getFilters(),
            ]);
    }

    function strToGBs($str) // converts a string like "2x2TBSATA2" into 4000 (result in gigabytes)
    {
        $str = trim($str);

        $arr = explode("TB", $str);
        $gbs_total = 0;

        if(!isset($arr[1])) // value is in GB
        {
            $arr = explode("GB", $str);
            $measure = "GB";
        }
        else // value is in TB
        {
            $arr = explode("TB", $str);
            $measure = "TB";
        }

        $values = explode("x", $arr[0]);
        //echo "<pre>values: ".print_r($values,1);
        if(!isset($values[1])) // Ex: 40TBSATA2
        {
            $gbs_total = $arr[0];
        }
        else
        {
            $gbs_total = $values[0] * $values[1];
        }

        if($measure == "TB") $gbs_total = $gbs_total * 1000;

        //echo "<pre>arr: ".print_r($arr,1); echo "\n\n value: $gbs_total";
        return $gbs_total;
    }

    function strToHDDType($str) // extract HDD type from strings like "2x2TBSATA2"
    {
        $str = trim($str);

        $arr = explode("TB", $str);

        if(!isset($arr[1])) // value is in GB
        {
            $arr = explode("GB", $str);
        }
        else // value is in TB
        {
            $arr = explode("TB", $str);
        }

        return $arr[1];
    }

    public function getFilters()
    {
        /* FILTERS (from the Excel file):

        Name    Type    Values
        Storage Range slider    0, 250GB, 500GB, 1TB, 2TB, 3TB, 4TB, 8TB, 12TB, 24TB, 48TB, 72TB
        Ram Checkboxes  2GB, 4GB, 8GB, 12GB, 16GB, 24GB, 32GB, 48GB, 64GB, 96GB
        Harddisk type   Dropdown    SAS, SATA, SSD
        Location    Dropdown    Refer to Location list
        */

        $filters = [
                    [
                        'name' => 'Storage',
                        'type' => 'range slider',
                        'defaultValue' => '24 TB',
                        'min' => 0,
                        'max' => 72,
                        'values' => ['0 GB', '250 GB', '500 GB', '1 TB', '2 TB', '3 TB', '4 TB', '8 TB', '12 TB', '24 TB', '48 TB', '72 TB'],
                    ],
                    [
                        'name' => 'RAM (GB)',
                        'type' => 'checkboxes',
                        'defaultValue' => 2,
                        'values' => [2, 4, 8, 12, 16, 24, 32, 48, 64, 96],
                    ],
                    [
                        'name' => 'Hard disk type',
                        'type' => 'dropdown list',
                        'defaultValue' => "(any)",
                        'values' => ["(any)", "SAS", "SATA2", "SSD"],
                    ],
                ];

        return $filters;
    }

    function convertUSDtoEUR($amount)
    {
      $exchangeRate = 0.96; // Current exchange rate as of 2023-10-09

      $convertedAmount = (float)$amount * $exchangeRate;

      return $convertedAmount;
    }

    function convertUSDtoEURWithAPI($amount)
    {
      $apiKey = 'YOUR_API_KEY'; // Replace this with your CurrencyConverter API key

      $url = 'https://free.currencyconverterapi.com/api/v6/convert?q=USD_EUR&compact=ultra&apiKey=' . $apiKey;

      $response = file_get_contents($url);

      $exchangeRate = json_decode($response)->conversion_rates->EUR;

      $convertedAmount = $amount * $exchangeRate;

      return $convertedAmount;
    }

    public function filterExcelAction(Request $request): JsonResponse
    {
        // Get the filter values from the view's form
        $filters = [];
        $filters['hdd'] = isset($_POST['range_value']) ? $_POST['range_value'] : "";
        $filters['ram'] = isset($_POST['ram_value']) ? $_POST['ram_value'] : "";
        $filters['hdd_type_value'] = isset($_POST['hdd_type_value']) ? $_POST['hdd_type_value'] : "";

        //echo "<pre>filters: ".print_r($filters, 1);

        // Fetch the data from the Excel file
        $data = $this->getFirst5ColumnsFromExcel($filters);

        //echo "<pre>data: ".print_r($data, 1);

        /*
        data: Array
        (
            [0] => Array
                (
                    [Model] => Dell R210Intel Xeon X3440
                    [RAM] => 16GBDDR3
                    [HDD] => 2x2TBSATA2
                    [Location] => AmsterdamAMS-01
                    [Price] => €49.99
                )

            [1] => Array
                (
                    [Model] => HP DL180G62x Intel Xeon E5620
                    [RAM] => 32GBDDR3
                    [HDD] => 8x2TBSATA2
                    [Location] => AmsterdamAMS-01
                    [Price] => €119.00
                )...
        */

        // apply filters to returned data
        foreach($data as $key => $value)
        {
            // remove dollar and euro sign from price column
            //$data[$key]['Price'] = substr($data[$key]['Price'], 1);

            // convert dollars to euros
            //$data[$key]['Price'] = $this->convertUSDtoEUR($data[$key]['Price']);

            // remove non macthing rows for HDD space
            $gbs_total = $this->strToGBs($value['HDD']); // get HDD value from excel file
            $filterHDD = $this->strToGBs($filters['hdd']); // get HDD value from filter
            if($gbs_total != $filterHDD) {
                unset($data[$key]);
            }

            // remove non macthing rows for RAM
            $ram_total = $this->strToGBs($value['RAM']); // get RAM value from excel file
            $filter_ram = $filters['ram']; // get RAM value from filter
            if($ram_total != $filter_ram) {
                unset($data[$key]);
            }

            // remove non macthing rows for HDD type
            $hdd_type = $this->strToHDDType($value['HDD']); // get HDD type from excel file
            $filter_hdd_type = $filters['hdd_type_value']; // get HDD type from filter
            if($filter_hdd_type == "(any)") continue;
            else
            {
                if($hdd_type != $filter_hdd_type) {
                    unset($data[$key]);
                }
            }
        }

        // order the array by price ascending
        //usort($data, fn($a, $b) => $a['Price'] <=> $b['Price']);

        //echo "<pre>data (after filtering): ".print_r($data, 1); die;

        return new JsonResponse($data);
    }

    function getFirst5ColumnsFromExcel(array $filters = []): array
    {
        // Get the Excel file path
        $excelFilePath = $this->getParameter('kernel.project_dir') . '/public/servers_all.xlsx';

        // Create a new reader object.
        $reader = IOFactory::createReader('Xlsx');

        // Load the Excel file.
        $spreadsheet = $reader->load($excelFilePath);

        // Get the worksheet.
        $worksheet = $spreadsheet->getActiveSheet();

        // Get the highest data row and column.
        $highestDataRow = $worksheet->getHighestDataRow();
        $highestDataColumn = $worksheet->getHighestDataColumn();

        // Create a new array to store the first 5 columns.
        $first5Columns = [];

        // Loop through the rows from 1 to the highest data row.
        for ($i = 1; $i <= $highestDataRow; $i++) {
            // Create a new array to store the first 5 columns of the row.
            $rowFirst5Columns = [];

            $column = 0;

            // Loop through the columns from A to the highest data column.
            for ($j = 'A'; $j <= $highestDataColumn; $j++)
            {
                switch($column)
                {
                    default:
                    case 0: $name = 'Model'; break;
                    case 1: $name = 'RAM'; break;
                    case 2: $name = 'HDD'; break;
                    case 3: $name = 'Location'; break;
                    case 4: $name = 'Price'; break;
                }

                // Get the cell value.
                $cellValue = $worksheet->getCell($j . $i)->getValue();

                // If the cell value is not empty, add it to the row first 5 columns array.
                if (!empty($cellValue)) {
                    $rowFirst5Columns[$name] = $cellValue;
                }

                $column++;
            }

            // If the row first 5 columns array is not empty, add it to the first 5 columns array.
            if (!empty($rowFirst5Columns)) {
                $first5Columns[] = $rowFirst5Columns;
            }
        }

        // Return the first 5 columns array.
        array_shift($first5Columns); // remove headers from excel file ("Model, RAM, etc")
        return $first5Columns;
    }
}
