<?php
/*
Contact us by zibal.ir if you find any issues.
*/
if (!defined('ABSPATH'))
    exit;

function Load_Zibal_Pos_Gateway()
{

    if (class_exists('WC_Payment_Gateway') && !class_exists('WC_Zibal_Pos_Gateway') && !function_exists('Woocommerce_Add_Zibal_Pos_Gateway')) {


        add_filter('woocommerce_payment_gateways', 'Woocommerce_Add_Zibal_Pos_Gateway');

        function Woocommerce_Add_Zibal_Pos_Gateway($methods)
        {
            $methods[] = 'WC_Zibal_Pos_Gateway';
            return $methods;
        }

    

        class WC_Zibal_Pos_Gateway extends WC_Payment_Gateway
        {

            public function __construct()
            {

                $this->id = 'WC_Zibal_Pos_Gateway';
                $this->method_title = __('پرداخت در محل زیبال', 'woocommerce');
                $this->method_description = __('تنظیمات پرداخت در محل زیبال برای افزونه فروشگاه ساز ووکامرس', 'woocommerce');
                $this->icon = apply_filters('WC_Zibal_Pos_logo', WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/assets/images/logo.png');
                $this->has_fields = false;

                $this->init_form_fields();
                $this->init_settings();

                $this->title = $this->settings['title'];
                $this->description = $this->settings['description'];

                $this->merchantId = $this->settings['merchantId'];
                $this->sandbox = $this->settings['sandbox'];
                $this->secretKey = $this->settings['secretKey'];

                $this->success_massage = $this->settings['success_massage'];
                $this->failed_massage = $this->settings['failed_massage'];

                if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>='))
                    add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
                else
                    add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));

                add_action('woocommerce_receipt_' . $this->id . '', array($this, 'Send_to_Zibal_Pos_Gateway'));
                add_action('woocommerce_api_' . strtolower(get_class($this)) . '', array($this, 'Return_from_Zibal_Pos_Gateway'));

            }


            public function admin_options()
            {


                parent::admin_options();
            }

            public function init_form_fields()
            {

                $this->form_fields = apply_filters('WC_Zibal_Pos_Gateway_Config', array(
                        'base_confing' => array(
                            'title' => __('تنظیمات پایه ای', 'woocommerce'),
                            'type' => 'title',
                            'description' => '',
                        ),
                        'enabled' => array(
                            'title' => __('فعالسازی/غیرفعالسازی', 'woocommerce'),
                            'type' => 'checkbox',
                            'label' => __('فعالسازی پرداخت در محل با زیبال', 'woocommerce'),
                            'description' => __('برای فعالسازی پرداخت در محل زیبال باید چک باکس را تیک بزنید', 'woocommerce'),
                            'default' => 'yes',
                            'desc_tip' => true,
                        ),
                        'title' => array(
                            'title' => __('عنوان', 'woocommerce'),
                            'type' => 'text',
                            'description' => __('عنوان درگاه که در طی خرید به مشتری نمایش داده میشود', 'woocommerce'),
                            'default' => __('پرداخت در محل با زیبال', 'woocommerce'),
                            'desc_tip' => true,
                        ),
                        'description' => array(
                            'title' => __('توضیحات درگاه', 'woocommerce'),
                            'type' => 'text',
                            'desc_tip' => true,
                            'description' => __('توضیحاتی که در طی عملیات پرداخت برای درگاه نمایش داده خواهد شد', 'woocommerce'),
                            'default' => __('پرداخت توسط دستگاه های کارتخوان به وسیله کلیه کارت های عضو شتاب از طریق زیبال', 'woocommerce')
                        ),
                        'account_confing' => array(
                            'title' => __('تنظیمات حساب زیبال', 'woocommerce'),
                            'type' => 'title',
                            'description' => '',
                        ),
                        'merchantId' => array(
                            'title' => __('merchantId', 'woocommerce'),
                            'type' => 'text',
                            'description' => __('مرچنت کد درگاه زیبال', 'woocommerce'),
                            'default' => '',
                            'desc_tip' => true	
                        ),
                        'secretKey' => array(
                            'title' => __('secretKey', 'woocommerce'),
                            'type' => 'text',
                            'description' => __('secretKey', 'woocommerce'),
                            'default' => '',
                            'desc_tip' => true
                        ),
                        'sandbox' => array(
                            'title' => __('حالت sandbox', 'woocommerce'),
                            'type' => 'checkbox',
                            'label' => __('برای تست در حالت سندباکس این گزینه را تیک بزنید', 'woocommerce'),
                            'description' => __('سندباکس پوز زیبال', 'woocommerce'),
                            'default' => 'yes',
                            'desc_tip' => true,
                        ),
                        'payment_confing' => array(
                            'title' => __('تنظیمات عملیات پرداخت', 'woocommerce'),
                            'type' => 'title',
                            'description' => '',
                        ),
                        'success_massage' => array(
                            'title' => __('پیام ثبت موفق در زیبال', 'woocommerce'),
                            'type' => 'textarea',
                            'description' => __('متن پیامی که میخواهید بعد از پرداخت موفق به کاربر نمایش دهید را وارد نمایید . همچنین می توانید از شورت کد {zibal_id} برای نمایش کد رهگیری (توکن) زیبال استفاده نمایید .', 'woocommerce'),
                            'default' => __('با تشکر از شما . با وارد کردن شناسه {zibal_id} در کارتخوان زیبال مبلغ را پرداخت کنید.', 'woocommerce'),
                        ),
                        'failed_massage' => array(
                            'title' => __('پیام ثبت ناموفق در زیبال', 'woocommerce'),
                            'type' => 'textarea',
                            'description' => __('متن پیامی که میخواهید بعد از پرداخت ناموفق به کاربر نمایش دهید را وارد نمایید . همچنین می توانید از شورت کد {fault} برای نمایش دلیل خطای رخ داده استفاده نمایید . این دلیل خطا از سایت زیبال ارسال میگردد .', 'woocommerce'),
                            'default' => __('پرداخت شما ناموفق بوده است . لطفا مجددا تلاش نمایید یا در صورت بروز اشکال با مدیر سایت تماس بگیرید .', 'woocommerce'),
                        ),
                    )
                );

            }

            public function process_payment($order_id)
            {
                $order = new WC_Order($order_id);
                return array(
                    'result' => 'success',
                    'redirect' => $order->get_checkout_payment_url(true)
                );
            }

            /**
             * @param $action (PaymentRequest, )
             * @param $params string
             *
             * @return mixed
             */
            public function SendRequestToZibal($action, $params)
            {
                try {
		    $server = ($this->sandbox=='yes')?'sandbox-api':'api';

                    $ch = curl_init('https://'.$server.'.zibal.ir/merchant/' . $action);
                    curl_setopt($ch, CURLOPT_USERAGENT, 'Zibal Pos Rest Api v1');
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Content-Type: application/json',
                        'Content-Length: ' . strlen($params)
                    ));
                    $result = curl_exec($ch);
                    return json_decode($result, true);
                } catch (Exception $ex) {
                    return false;
                }
            }

            public function Send_to_Zibal_Pos_Gateway($order_id)
            {


                global $woocommerce;
                $order = new WC_Order($order_id);
                $currency = $order->get_order_currency();
                $currency = apply_filters('WC_Zibal_Pos_Currency', $currency, $order_id);


                $form = '<form action="" method="POST" class="Zibal-checkout-form" id="Zibal-checkout-form">
						<input type="submit" name="Zibal_Pos_submit" class="button alt" id="Zibal-payment-button" value="' . __('پرداخت', 'woocommerce') . '"/>
						<a class="button cancel" href="' . $woocommerce->cart->get_checkout_url() . '">' . __('بازگشت', 'woocommerce') . '</a>
					 </form><br/>';
                $form = apply_filters('WC_Zibal_Pos_Form', $form, $order_id, $woocommerce);

                do_action('WC_Zibal_Pos_Gateway_Before_Form', $order_id, $woocommerce);
                echo $form;
                do_action('WC_Zibal_Pos_Gateway_After_Form', $order_id, $woocommerce);


                $Amount = intval($order->order_total);
               

                $Amount = apply_filters('woocommerce_order_amount_total_Zibal_Pos_gateway', $Amount, $currency);

                $merchantId = $this->merchantId;
                $secretKey = $this->secretKey;

                $CallbackUrl = add_query_arg('wc_order', $order_id, WC()->api_request_url('WC_Zibal_Pos_Gateway'));

                $products = array();
                $order_items = $order->get_items();
                foreach ((array)$order_items as $product) {
                    $products[] = $product['name'] . ' (' . $product['qty'] . ') ';
                }
                $products = implode(' - ', $products);

                $Description = 'خریدار : ' . $order->billing_first_name . ' ' . $order->billing_last_name . ' | محصولات : ' . $products;
                $Mobile = get_post_meta($order_id, '_billing_phone', true) ? get_post_meta($order_id, '_billing_phone', true) : '-';

                //Hooks for iranian developer
                $Description = apply_filters('WC_Zibal_Pos_Description', $Description, $order_id);
                $Mobile = apply_filters('WC_Zibal_Pos_Mobile', $Mobile, $order_id);
                do_action('WC_Zibal_Pos_Gateway_Payment', $order_id, $Description, $Mobile);

                $parameters = array(
                    "merchantId"=>$merchantId,
                    "secretKey"=> $secretKey,
                    "orderId"=> $order->get_order_number(),
                    "callbackUrl"=> $CallbackUrl,
                    "amount"=> $Amount,
                    "description"=> $Description
                );

                $result = $this->SendRequestToZibal('addOrder',json_encode($parameters));


			


                if ($result === false) {
                    echo "cURL Error #:" ;
                } else {
                    if ($result["result"] == 1) {
                        update_post_meta($order_id, '_transaction_id', $result['zibalId']);
                        $woocommerce->cart->empty_cart();

                        $Note = sprintf(__('سفارش با موفقیت ثبت شد. لطفا کد s% را روی دستگاه کارتخوان وارد کنید.<br/>', 'woocommerce'), $result['zibalId']);
                        $Note = apply_filters('WC_Zibal_Pos_Return_from_Gateway_Success_Note', $Note, $order_id, $result['zibalId']);
                        if ($Note)
                            $order->add_order_note($Note, 1);


                        $Notice = wpautop(wptexturize($this->success_massage));

                        $Notice = str_replace("{zibal_id}", $result['zibalId'], $Notice);

                        $Notice = apply_filters('WC_Zibal_Pos_Return_from_Gateway_Success_Notice', $Notice, $order_id, $Transaction_ID);
                        if ($Notice)
                            wc_add_notice($Notice, 'success');

                        do_action('WC_Zibal_Pos_Return_from_Gateway_Success', $order_id, $Transaction_ID);

                        wp_redirect(add_query_arg('wc_status', 'success', $this->get_return_url($order)));
                        exit;
                    } else {
                        $Message = ' تراکنش ناموفق بود- کد خطا : ' . $result["result"].' '.$result['message'];
                        $Fault = '';
                    }
                }

                if (!empty($Message) && $Message) {

                    $Note = sprintf(__('خطا در هنگام ارسال به بانک : %s', 'woocommerce'), $Message);
                    $Note = apply_filters('WC_Zibal_Pos_Send_to_Gateway_Failed_Note', $Note, $order_id, $Fault);
                    $order->add_order_note($Note);
			$Message = $result['message'];

                    $Notice = sprintf(__('در هنگام اتصال به زیبال خطای زیر رخ داده است : <br/>%s', 'woocommerce'), $Message);
                    $Notice = apply_filters('WC_Zibal_Pos_Send_to_Gateway_Failed_Notice', $Notice, $order_id, $Fault);
                    if ($Notice)
                        wc_add_notice($Notice, 'error');

                    do_action('WC_Zibal_Pos_Send_to_Gateway_Failed', $order_id, $Fault);
                }
            }


            public function Return_from_Zibal_Pos_Gateway()
            {

                $zibalData = $_POST;

                $InvoiceNumber = $zibalData['orderId'];
		if ($_GET['wc_order']!=$InvoiceNumber)
die();
                global $woocommerce;

                $order_id = $InvoiceNumber;


                if ($order_id) {

                    $order = new WC_Order($order_id);
                    $currency = $order->get_order_currency();
                    $currency = apply_filters('WC_Zibal_Pos_Currency', $currency, $order_id);

                    if ($order->status != 'completed') {


                            $Amount = intval($order->order_total);
                        



                        $Status = 'completed';
                        $Transaction_ID = $zibalData['zibalId'];
                        $Fault = '';
                        $Message = '';


                        if ($Status == 'completed' && isset($Transaction_ID) && $Transaction_ID != 0) {

                            update_post_meta($order_id, '_transaction_id', $Transaction_ID);


                            $order->payment_complete($Transaction_ID);
                            $woocommerce->cart->empty_cart();

                            $Note = sprintf(__('اعلام پرداخت موفق با کد مرجع s%', 'woocommerce'), $zibalData['refNumber']);
                            $Note = apply_filters('WC_Zibal_Pos_Return_from_Gateway_Success_Note', $Note, $order_id, $Transaction_ID);
                            if ($Note)
                                $order->add_order_note($Note, 1);


                            $Notice = wpautop(wptexturize($this->success_massage));

                            $Notice = str_replace("{zibal_id}", $Transaction_ID, $Notice);

                            $Notice = apply_filters('WC_Zibal_Pos_Return_from_Gateway_Success_Notice', $Notice, $order_id, $Transaction_ID);
                            if ($Notice)
                                wc_add_notice($Notice, 'success');
                            echo json_encode(array("success"=>true));

                            exit;
                        } else {


                            $tr_id = ($Transaction_ID && $Transaction_ID != 0) ? ('<br/>توکن : ' . $Transaction_ID) : '';

                            $Note = sprintf(__('خطا در هنگام بازگشت از بانک : %s %s', 'woocommerce'), $Message, $tr_id);

                            $Note = apply_filters('WC_Zibal_Pos_Return_from_Gateway_Failed_Note', $Note, $order_id, $Transaction_ID, $Fault);
                            if ($Note)
                                $order->add_order_note($Note, 1);

                            $Notice = wpautop(wptexturize($this->failed_massage));

                            $Notice = str_replace("{zibal_id}", $Transaction_ID, $Notice);

                            $Notice = str_replace("{fault}", $Message, $Notice);
                            $Notice = apply_filters('WC_Zibal_Pos_Return_from_Gateway_Failed_Notice', $Notice, $order_id, $Transaction_ID, $Fault);
                            if ($Notice)
                                wc_add_notice($Notice, 'error');

                            do_action('WC_Zibal_Pos_Return_from_Gateway_Failed', $order_id, $Transaction_ID, $Fault);

                            wp_redirect($woocommerce->cart->get_checkout_url());
                            exit;
                        }
                    } else {


                        $Transaction_ID = get_post_meta($order_id, '_transaction_id', true);

                        $Notice = wpautop(wptexturize($this->success_massage));

                        $Notice = str_replace("{zibal_id}", $Transaction_ID, $Notice);

                        $Notice = apply_filters('WC_Zibal_Pos_Return_from_Gateway_ReSuccess_Notice', $Notice, $order_id, $Transaction_ID);
                        if ($Notice)
                            wc_add_notice($Notice, 'success');


                        do_action('WC_Zibal_Pos_Return_from_Gateway_ReSuccess', $order_id, $Transaction_ID);

                        wp_redirect(add_query_arg('wc_status', 'success', $this->get_return_url($order)));
                        exit;
                    }
                } else {


                    $Fault = __('شماره سفارش وجود ندارد .', 'woocommerce');
                    $Notice = wpautop(wptexturize($this->failed_massage));
                    $Notice = str_replace("{fault}", $Fault, $Notice);
                    $Notice = apply_filters('WC_Zibal_Pos_Return_from_Gateway_No_Order_ID_Notice', $Notice, $order_id, $Fault);
                    if ($Notice)
                        wc_add_notice($Notice, 'error');

                    do_action('WC_Zibal_Pos_Return_from_Gateway_No_Order_ID', $order_id, $Transaction_ID, $Fault);

                    wp_redirect($woocommerce->cart->get_checkout_url());
                    exit;
                }
            }

        }

    }
}

add_action('plugins_loaded', 'Load_Zibal_Pos_Gateway', 0);
