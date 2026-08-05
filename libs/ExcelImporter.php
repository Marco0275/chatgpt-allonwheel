<?php
declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelImporter
{

    private DB $db;

    private EditorialQueue $queue;

    public function __construct(DB $db)
    {
        $this->db = $db;
        $this->queue = new EditorialQueue($db);
    }

    public function import(string $file): int
    {

        $spreadsheet = IOFactory::load($file);

        $sheet = $spreadsheet->getActiveSheet();

        $rows = $sheet->toArray();

        $count = 0;

        foreach ($rows as $index => $row)
        {

            if ($index == 0)
            {
                continue;
            }

            if (trim((string)$row[0]) == '')
            {
                continue;
            }

            $publish = trim((string)$row[0]);

            $language = strtoupper(trim((string)$row[1]));

            $category = trim((string)$row[2]);

            $title = trim((string)$row[3]);

            $keyword = trim((string)$row[4]);

            $secondary = trim((string)$row[5]);

            $words = (int)$row[6];

            if ($words <= 0)
            {
                $words = 1500;
            }

            $this->queue->create([

                'uuid' => $this->uuid(),

                'publish_at' => $publish,

                'priority' => 5,

                'language' => $language,

                'category' => $category,

                'title' => $title,

                'keyword' => $keyword,

                'secondary_keywords' => $secondary,

                'target_words' => $words,

                'source_file' => basename($file),

                'source_row' => $index + 1

            ]);

            $count++;

        }

        return $count;

    }

    private function uuid(): string
    {

        return sprintf(

            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

            mt_rand(0,65535),

            mt_rand(0,65535),

            mt_rand(0,65535),

            mt_rand(16384,20479),

            mt_rand(32768,49151),

            mt_rand(0,65535),

            mt_rand(0,65535),

            mt_rand(0,65535)

        );

    }

}