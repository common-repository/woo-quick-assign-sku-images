<?php
/**
 * Copyright (c) 2018 EXTREME IDEA LLC https://www.extreme-idea.com
 * This software is the proprietary information of EXTREME IDEA LLC.
 *
 * All Rights Reserved.
 * Modification, redistribution and use in source and binary forms, with or without modification
 * are not permitted without prior written approval by the copyright holder.
 *
 */

namespace Com\ExtremeIdea\Woocommerce\QuickAssignSkuImages\View;

use Symfony\Component\HttpFoundation\Request;

/**
 * Class AdminPage
 *
 * @package Com\ExtremeIdea\Woocommerce\Quick\Assign\Sku\Images\View
 */
class AdminPage
{

    const SUBMIT_KEY = 'wqasi_submit_form';
    const SKU_REGEX_KEY = 'wqasi_sku_regex_form';

    /**
     * Forbidden symbols in file name(Windows)
     */
    const FORBIDDEN_SYMBOLS = ['%bs' => '\\', '%fs' => '/', '%cn' => ':', '%ak' => '*', '%qm' => '?', '%lt' => '<',
        '%gt' => '>', '%vb' => '|'];

    public $request;

    public function __construct()
    {
        $request = new Request();
        $this->request = $request->createFromGlobals();
    }

    /**
     * Render page and include other actions.
     *
     * @return void.
     */
    public function render()
    {
        $this->checkPermission();
        $this->saveSkuRegex();
        $this->submit();
        $this->renderPage();
    }

    /**
     * Render Page.
     *
     * @return void.
     */
    protected function renderPage()
    {
        $table = "<table border='1'><tbody><tr>";
        $tHeader = array_map(
            function ($value) {
                return "<th>$value</th>";
            },
            static::FORBIDDEN_SYMBOLS
        );
        $tBody = array_map(
            function ($value) {
                return "<th>$value</th>";
            },
            array_keys(static::FORBIDDEN_SYMBOLS)
        );
        $table .= "<tr>" . implode('', $tHeader) . "</tr>";
        $table .= "<tr>" . implode('', $tBody) . "</tr>";
        $table .= "</tr></tbody></table>";
        echo "
            <h1>Quick Assign SKU Images For WooCommerce</h1>
            
            <br/>
            
            <div id='wqasi-form'>
                <form method='POST' enctype='multipart/form-data'>
                    Select images: 
                    <input type='file' name='images[]' multiple accept='image/jpeg, image/jpg, image/png, image/gif'>
                    <input type='submit' name='" . self::SUBMIT_KEY . "' value='Upload'>
                </form>                       
            </div>  
            
            <br/>
            <br/>            
            
            <div id='wqasi-form'>
                <form method='POST'>
                    <h1>Settings</h1>
                    <table>
                        <tbody>
                            <tr>
                                <td>
                                    SKU regex (<b>advanced users</b>):
                                </td>
                                <td>
                                    <input type='text' name='skuRegex' value='". get_option('wqasi_sku_regex', ''). "'>
                                </td>                                                      
                                <td></td>
                            </tr>
                            <tr><td colspan='3'><i>Use this setting for extract sku from filename, 
                                    if there no matches found, will be used full file name as sku 
                                    </i></td></tr>
                            <tr></tr>
                            <tr>
                                <td></td>
                                <td></td>
                                <td>
                                    <input type='submit' name='" . self::SKU_REGEX_KEY . "' value='Save settings'>
                                </td>                            
                            </tr>                         
                        </tbody>                   
                    </table>
                </form>                       
            </div>
            
            <br>
            
            <h1>How to use the plugin:</h1>
            <div>     
                <p>1. Create a product in WooCommerce and copy its SKU;</p>
                <p>2. Rename an image on your local system - paste saved SKU;</p>
                <p>3. If product SKU contains a forbidden symbols, please replace them in image filename by 
                using the table below;
                </p>
                $table
                <p>
                    For example: if your product SKU=`3434*3`, name your image 
                    file as `3434%ak3` and upload via this plugin.<br>
                </p>
                <p>4. Select image via Select images button;</p>
                <p>5. Press upload and check out your product.</p>
                <p>Result: your product image was added/updated.</p>     
                <p><b>Settings</b></p>
                <p>SKU regex - this parameter will help's you to get SKU from image file 
                name according to a specific pattern(regex). <br>
                If there no matches found, will be used full file name as sku <br>
                <i>SKU regex example: if you have many products images with names,
                    like '57266_RSP3109_R2_100.jpg', where product 
                    sku is the first part of image name (57266), 
                    you can set SKU regex = ([^_]+) and plugin will be get sku by 
                    first patern match from image file name                 
                    </i>
                </p>
            </div>       
        ";
    }

    /**
     * Submit Action
     *
     * @return mixed.
     */
    protected function submit()
    {
        if ($this->request->getMethod() == 'POST' && $this->request->get(self::SUBMIT_KEY)) {
            $images = $this->request->files->get('images');
            $skuRegex = get_option('wqasi_sku_regex');
            $success = 0;
            $timeStart = microtime(true);
            if (!$images) {
                $this->showMessage("No file(s) chosen. Please choose jpg, jpeg, gif or png file(s).", '', 'error');
                return false;
            }
            foreach ($images as $image) {
                if (!$image->isValid()) {
                    $this->showMessage("Upload file error: {$image->getErrorMessage()}", '', 'error');
                    continue;
                }

                $fileName = str_replace(
                    array_keys(static::FORBIDDEN_SYMBOLS),
                    array_values(static::FORBIDDEN_SYMBOLS),
                    $image->getClientOriginalName()
                );

                // By default full name
                $sku = substr($fileName, 0, strrpos($fileName, '.'));
                // If set regex extract sku by pattern
                if ($skuRegex) {
                    preg_match('/' . $skuRegex . '/', $sku, $match);
                    $sku = $match[0] ?? $sku;
                }

                $productId = wc_get_product_id_by_sku($sku);
                if (!$productId) {
                    $this->showMessage("Product with sku: '$sku' not found!", '', 'error');
                    continue;
                }

                $file = [];
                $file['name'] = $fileName;
                $file['tmp_name'] = $image->getPathname();
                $attachmentId = media_handle_sideload($file, $productId);
                if (is_wp_error($attachmentId)) {
                    $this->showMessage("Create attachment error: {$attachmentId->get_error_message()}");
                    continue;
                }
                $product = wc_get_product($productId);
                $product->set_image_id($attachmentId);
                if ($product->save()) {
                    $success++;
                }
            }

            $timeEnd = microtime(true);
            $totalTime = number_format($timeEnd - $timeStart, 1, '.', '');
            $errors = count($images) - $success;
            $this->showMessage("Time: $totalTime s, Success: $success Errors: $errors");
        }
    }

    protected function saveSkuRegex()
    {
        if ($this->request->getMethod() == 'POST' && $this->request->get(self::SKU_REGEX_KEY)) {
            $skuRegex = $this->request->get('skuRegex');

            if (!get_option('wqasi_sku_regex') && $skuRegex == null) {
                add_option('wqasi_sku_regex', $skuRegex);
                return;
            }
            update_option('wqasi_sku_regex', $skuRegex);
        }
    }

    /**
     * Check user permission.
     *
     * @return void.
     */
    protected function checkPermission()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
    }

    /**
     * Show message to Admin
     *
     * @param string $message     Main message
     * @param string $subMesssage sub message
     * @param string $type        class for <div> element
     *
     * @return void
     */
    public function showMessage($message, $subMesssage = '', $type = 'notice notice-success')
    {

        echo "
            <div class='$type'>
                <p>
                    <strong>$message</strong>
                </p>
                <p>
                    $subMesssage                
                </p>
            </div>
            ";
    }
}
