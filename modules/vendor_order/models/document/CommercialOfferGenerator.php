<?php

namespace app\modules\vendor_order\models\document;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\TemplateProcessor;

class CommercialOfferGenerator
{
    private $phpWord;

    public function __construct($templatePath)
    {
//        $this->phpWord = new PhpWord();
        $this->phpWord = new TemplateProcessor($templatePath);
    }

    public function generate(array $offerData, string $filename)
    {
        $this->phpWord->cloneRow('article', count($offerData['products']));

        foreach ($offerData['products'] as $index => $product) {
            $n = $index + 1;

            $product['quantity'] > 0 ? $quantity = $product['quantity'] : $quantity = 1;
            
            $this->phpWord->setValue("name#{$n}", $product['name'] ?? '');
            $this->phpWord->setValue("article#{$n}", $product['article'] ?? '');
            $this->phpWord->setValue("brand#{$n}", $product['brand'] ?? '');
            $this->phpWord->setValue("delivery#{$n}", $product['deliveryTime'] ?? '');
            $this->phpWord->setValue("quantity#{$n}", $quantity ?? '');
            $this->phpWord->setValue("price#{$n}", ($quantity * $product['price']).' руб.' ?? '');
        }

//        $section = $this->phpWord->addSection();

        $this->phpWord->saveAs($filename);

        // Добавляем заголовок
//        $section->addText('Коммерческое предложение', ['bold' => true, 'size' => 16]);

        // Добавляем таблицу с товарами
//        $this->addProductsTable($section, $offerData['products']);

        // Добавляем итоговую сумму
//        $section->addText('Итого: ' . number_format($offerData['totalPrice'], '2', ',', ' ') . ' руб.', ['bold' => true, 'align' => 'right']);

        // Сохраняем документ
//        $this->saveDocument($filename);

        return $filename;
    }

    private function addProductsTable($section, array $products): void
    {
        $table = $section->addTable(['borderSize' => 6]);

        // Заголовки таблицы
        $table->addRow();
        $table->addCell(2000)->addText('№', ['bold' => true, 'align' => 'center']);
        $table->addCell(4000)->addText('Наименование', ['bold' => true, 'align' => 'center']);
        $table->addCell(4000)->addText('Артикул', ['bold' => true, 'align' => 'center']);
        $table->addCell(4000)->addText('Поставщик', ['bold' => true, 'align' => 'center']);
        $table->addCell(4000)->addText('Срок поставки', ['bold' => true, 'align' => 'center']);
        $table->addCell(2000)->addText('Цена', ['bold' => true, 'align' => 'center']);

        // Данные товаров
        foreach ($products as $i => $product) {
            $table->addRow();
            $table->addCell()->addText($i + 1);
            $table->addCell()->addText($product['name'], ['align' => 'center']);
            $table->addCell()->addText($product['article'], ['align' => 'center']);
            $table->addCell()->addText($product['supplier'], ['align' => 'center']);
            $table->addCell()->addText($product['deliveryTime'], ['align' => 'center']);
            $table->addCell()->addText(number_format($product['price'], 2, ',', ' ') . ' руб.', ['align' => 'center']);
        }
    }

    private function saveDocument(string $filename): void
    {
        $objWriter = IOFactory::createWriter($this->phpWord, 'Word2007');
        $objWriter->save($filename);
    }
}