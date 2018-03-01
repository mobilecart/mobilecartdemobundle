<?php

namespace MobileCart\DemoBundle\Command;

use MobileCart\CoreBundle\Constants\EntityConstants;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CartDemoInstallCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('cart:demo:install')
            ->setDescription('Install Test Data')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityService = $this->getContainer()->get('cart.entity');
        $objectType = EntityConstants::PRODUCT;

        $vars = [
            'color' => [
                'datatype' => 'varchar',
                'url_key' => 'color',
                'label' => 'Color',
                'form_input' => 'select',
                'options' => [
                    'white' => 'White',
                    'red' => 'Red',
                    'black' => 'Black',
                    'grey' => 'Grey'
                ],
            ],
            'size' => [
                'datatype' => 'varchar',
                'url_key' => 'size',
                'label' => 'Size',
                'form_input' => 'select',
                'options' => [
                    'small' => 'Small',
                    'medium' => 'Medium',
                    'large' => 'Large',
                    'xl' => 'XL',
                ],
            ],
            'slogan' => [
                'datatype' => 'varchar',
                'url_key' => 'slogan',
                'label' => 'Slogan',
                'form_input' => 'text',
            ],
        ];

        $varSet = $entityService->findOneBy(EntityConstants::ITEM_VAR_SET, [
            'object_type' => EntityConstants::PRODUCT,
        ]);

        if (!$varSet) {
            $output->writeln("Product Variant Set not found");
            return;
        }

        foreach($vars as $code => $data) {

            $label = $data['label'];
            $dataType = $data['datatype'];
            $urlKey = $data['url_key'];
            $formInput = $data['form_input'];

            // check if it's already there
            $itemVar = $entityService->findOneBy(EntityConstants::ITEM_VAR, [
                'code' => $code,
            ]);

            if ($itemVar) {
                $output->writeln("Skipping '{$code}' : row found");
                continue;
            }

            $itemVar = $entityService->getInstance(EntityConstants::ITEM_VAR);

            $itemVar->setName($label)
                ->setCode($code)
                ->setDatatype($dataType)
                ->setUrlToken($urlKey)
                ->setFormInput($formInput);

            $entityService->persist($itemVar);

            $varSetVar = $entityService->getInstance(EntityConstants::ITEM_VAR_SET_VAR);
            $varSetVar->setItemVar($itemVar)
                ->setItemVarSet($varSet);

            $entityService->persist($varSetVar);

            $message = "Created ItemVar: {$label} for Object Type: {$objectType}";
            $output->writeln($message);

            if (isset($data['options'])) {
                $options = $data['options'];
                foreach($options as $optionKey => $optionValue) {
                    $option = $entityService->getInstance(EntityConstants::ITEM_VAR_OPTION_VARCHAR);
                    $option->setItemVar($itemVar)
                        ->setUrlValue($optionKey)
                        ->setValue($optionValue);

                    $entityService->persist($option);
                    $message = "Added Option: " . $optionKey;
                    $output->writeln($message);
                }
            }
        }

        $output->writeln('Creating products');

        $itemVarSize = $entityService->findOneBy('item_var', [
            'code' => 'size',
        ]);

        $itemVarColor = $entityService->findOneBy('item_var', [
            'code' => 'color',
        ]);

        $pCatData = [
            'slug' => 'shirts',
            'name' => 'Shirts',
            'content' => '',
            'custom_template' => '', // todo
        ];

        $parentCategory = $entityService->findOneBy('category', [
            'slug' => $pCatData['slug'],
        ]);

        if (!$parentCategory) {
            $parentCategory = $entityService->getInstance('category');
            $parentCategory->fromArray($pCatData);
            $entityService->persist($parentCategory);
            $output->writeln("Created Category");
        }

        $products = [
            [
                'slug' => 'basic-tee',
                'sku' => 'basic-tee',
                'name' => 'Basic T-Shirt',
                'price' => '2.00',
                'min_qty' => 1,
                'content_parts' => [
                    'The minimum quantity for this item is 12 units',
                    'Double-needle stitching throughout',
                    'Made of 100% preshrunk, heavyweight cotton',
                    'Imported',
                ],
                'colors' => [
                    'Red',
                    'White'
                ],
                'sizes' => [
                    [
                        'value' => 'Small',
                        'addtl_price' => '0'
                    ],
                    [
                        'value' => 'Medium',
                        'addtl_price' => '0'
                    ],
                    [
                        'value' => 'Large',
                        'addtl_price' => '0'
                    ],
                    [
                        'value' => 'XL',
                        'addtl_price' => '0'
                    ],
                ],
                'images' => [
                    'tshirt_red_front.png',
                    'tshirt_white_front.png',
                ],
            ],
        ];

        foreach($products as $pData) {

            $p = $entityService->findOneBy('product', [
                'slug' => $pData['slug'],
            ]);

            $content = "<ul>";
            foreach($pData['content_parts'] as $part) {
                $content .= "<li>{$part}</li>";
            }
            $content .= "</ul>";

            if (!$p) {

                $p = $entityService->getInstance('product');
                $p->setItemVarSet($varSet)
                    ->setCurrency('USD')
                    ->setSlug($pData['slug'])
                    ->setSku($pData['sku'])
                    ->setName($pData['name'])
                    ->setPrice($pData['price'])
                    ->setMinQty($pData['min_qty'])
                    ->setContent($content)
                    ->setType(2) // configurable
                    ->setPageTitle($pData['slug'])
                    ->setShortDescription($pData['slug'])
                    ->setQty(100)
                    ->setIsQtyManaged(0)
                    ->setIsInStock(1)
                    ->setIsPublic(1)
                    ->setIsEnabled(1)
                    ->setIsTaxable(1)
                    ->setVisibility(1)
                ;

                $entityService->persist($p);

                $output->writeln("Created Configurable Product: " . $pData['sku']);

                $cp = $entityService->getInstance('category_product');
                $cp->setCategory($parentCategory)
                    ->setProduct($p);

                $entityService->persist($cp);

                $output->writeln("Associated product to Category");

                $x = 0;
                foreach($pData['images'] as $file) {
                    $pi = $entityService->getInstance('product_image');
                    $pi->setParent($p)
                        ->setPath('bundles/mobilecartdemo/images/' . $file)
                        ->setCode('list_grid');

                    if (!$x) {
                        $pi->setIsDefault(1);
                    }

                    $entityService->persist($pi);

                    $x++;
                }
            }

            foreach($pData['colors'] as $color) {
                if ($pData['sizes']) {
                    // create a simple for each color and size
                    foreach($pData['sizes'] as $sizeData) {

                        $size = $sizeData['value'];

                        $spSlug = $pData['slug'] . '-' . strtolower($color) . '-' . str_replace('-', '', strtolower($size));

                        $sp = $entityService->findOneBy('product', [
                            'slug' => $spSlug,
                        ]);

                        if (!$sp) {
                            $sp = $entityService->getInstance('product');
                            $sp->setItemVarSet($varSet)
                                ->setCurrency('USD')
                                ->setSlug($spSlug)
                                ->setSku($spSlug)
                                ->setName($pData['name'])
                                ->setPrice($pData['price'])
                                ->setMinQty($pData['min_qty'])
                                ->setContent($content)
                                ->setType(1) // simple
                                ->setPageTitle($pData['slug'])
                                ->setShortDescription($pData['slug'])
                                ->setQty(100)
                                ->setIsQtyManaged(0)
                                ->setIsInStock(1)
                                ->setIsPublic(0)
                                ->setIsEnabled(1)
                                ->setIsTaxable(1)
                                ->setVisibility(1)
                            ;

                            $entityService->persist($sp);

                            $entityService->persistVariants($sp, [
                                'color' => $color,
                                'size' => $size,
                            ]);

                            $output->writeln("Created Simple Product: " . $spSlug . ", color: {$color} , size: {$size}");

                            $cp = $entityService->getInstance('category_product');
                            $cp->setCategory($parentCategory)
                                ->setProduct($sp);

                            $entityService->persist($cp);

                            if ($color == 'Red') {

                                $spi = $entityService->getInstance('product_image');
                                $spi->setParent($sp)
                                    ->setPath('bundles/mobilecartdemo/images/tshirt_red_front.png')
                                    ->setCode('list_grid');

                                $entityService->persist($spi);

                            } else {

                                $spi = $entityService->getInstance('product_image');
                                $spi->setParent($sp)
                                    ->setPath('bundles/mobilecartdemo/images/tshirt_white_front.png')
                                    ->setCode('list_grid');

                                $entityService->persist($spi);

                            }
                        }

                        $pcSize = $entityService->getInstance('product_config');
                        $pcSize->setProduct($p)
                            ->setChildProduct($sp)
                            ->setItemVar($itemVarSize);

                        $entityService->persist($pcSize);

                        $pcColor = $entityService->getInstance('product_config');
                        $pcColor->setProduct($p)
                            ->setChildProduct($sp)
                            ->setItemVar($itemVarColor);

                        $entityService->persist($pcColor);
                    }
                }
            }

        }

        $output->writeln("Data Imported. \n\nPlease run the re-configure command: app/console cart:product:reconfigure");
    }

}
