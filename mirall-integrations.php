<?php
/**
 * Plugin Name: Mirall Integrations
 * Description: مرکز اتصال‌های Mirall Beauty برای پرداخت، Callback/Webhook و وضعیت اتصال پیامک.
 * Version: 1.0.0
 * Author: Mirall Beauty
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Mirall_Integrations {
    const OPTION = 'mirall_integration_settings';

    public static function init() : void {
        add_action( 'admin_menu', [ __CLASS__, 'admin_menu' ], 30 );
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
        add_action( 'add_meta_boxes_mirall_booking', [ __CLASS__, 'booking_meta_box' ] );
        add_action( 'save_post_mirall_booking', [ __CLASS__, 'save_booking_meta' ], 20, 2 );
        add_action( 'save_post_mirall_booking', [ __CLASS__, 'ensure_payment_defaults' ], 25, 2 );
        add_filter( 'do_shortcode_tag', [ __CLASS__, 'enhance_account_shortcode' ], 20, 4 );
        add_action( 'admin_post_mirall_payment_start', [ __CLASS__, 'start_payment' ] );
        add_action( 'admin_post_nopriv_mirall_payment_callback', [ __CLASS__, 'payment_callback' ] );
        add_action( 'admin_post_mirall_payment_callback', [ __CLASS__, 'payment_callback' ] );
        add_action( 'rest_api_init', [ __CLASS__, 'register_rest_routes' ] );
    }

    public static function defaults() : array {
        return [
            'payment_enabled' => '0',
            'provider' => 'custom',
            'merchant_id' => '',
            'api_key' => '',
            'request_endpoint' => '',
            'verify_endpoint' => '',
            'sandbox' => '1',
            'currency' => 'IRR',
            'default_amount' => '100000',
            'webhook_secret' => '',
        ];
    }

    public static function settings() : array {
        return wp_parse_args( (array) get_option( self::OPTION, [] ), self::defaults() );
    }

    public static function admin_menu() : void {
        add_submenu_page(
            'mirall-luxe',
            'اتصال‌ها و پرداخت',
            'اتصال‌ها و پرداخت',
            'manage_options',
            'mirall-integrations',
            [ __CLASS__, 'settings_page' ]
        );
    }

    public static function register_settings() : void {
        register_setting( 'mirall_integrations', self::OPTION, [ 'sanitize_callback' => [ __CLASS__, 'sanitize_settings' ] ] );
    }

    public static function sanitize_settings( $input ) : array {
        $input = is_array( $input ) ? $input : [];
        $out = self::defaults();
        $out['payment_enabled'] = empty( $input['payment_enabled'] ) ? '0' : '1';
        $provider = sanitize_key( $input['provider'] ?? 'custom' );
        $out['provider'] = in_array( $provider, [ 'custom', 'zarinpal', 'idpay', 'manual' ], true ) ? $provider : 'custom';
        foreach ( [ 'merchant_id', 'api_key', 'webhook_secret' ] as $key ) {
            $out[ $key ] = sanitize_text_field( $input[ $key ] ?? '' );
        }
        foreach ( [ 'request_endpoint', 'verify_endpoint' ] as $key ) {
            $out[ $key ] = esc_url_raw( $input[ $key ] ?? '' );
        }
        $out['sandbox'] = empty( $input['sandbox'] ) ? '0' : '1';
        $out['currency'] = in_array( $input['currency'] ?? 'IRR', [ 'IRR', 'IRT' ], true ) ? $input['currency'] : 'IRR';
        $out['default_amount'] = (string) max( 0, absint( $input['default_amount'] ?? 0 ) );
        return $out;
    }

    public static function settings_page() : void {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        $s = self::settings();
        $callback = admin_url( 'admin-post.php?action=mirall_payment_callback' );
        $webhook = rest_url( 'mirall/v1/payment/webhook' );
        $sms = function_exists( 'mirall_luxe_sms_settings' ) ? mirall_luxe_sms_settings() : [];
        $sms_ready = ! empty( $sms['enabled'] ) && ! empty( $sms['api_key'] );
        ?>
        <div class="wrap" dir="rtl">
            <h1>اتصال‌ها و پرداخت میرال</h1>
            <p>تا زمانی که اطلاعات واقعی سرویس وارد و پرداخت فعال نشود، هیچ تراکنش واقعی ارسال نمی‌شود.</p>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;margin:20px 0">
                <div style="background:#fff;padding:18px;border:1px solid #ddd;border-radius:12px">
                    <h2 style="margin-top:0">پنل پیامکی</h2>
                    <p>وضعیت: <strong><?php echo $sms_ready ? 'آماده و فعال' : 'آماده اتصال'; ?></strong></p>
                    <p>کاوه‌نگار و API سفارشی JSON پشتیبانی می‌شوند.</p>
                    <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=mirall-sms' ) ); ?>">تنظیمات پیامک</a>
                </div>
                <div style="background:#fff;padding:18px;border:1px solid #ddd;border-radius:12px">
                    <h2 style="margin-top:0">درگاه پرداخت</h2>
                    <p>وضعیت: <strong><?php echo '1' === $s['payment_enabled'] ? 'فعال' : 'آماده اتصال'; ?></strong></p>
                    <p>Provider، Merchant/API، Sandbox، مبلغ هر رزرو و Verify آماده است.</p>
                </div>
                <div style="background:#fff;padding:18px;border:1px solid #ddd;border-radius:12px">
                    <h2 style="margin-top:0">Callback / Webhook</h2>
                    <p><strong>Callback:</strong><br><code><?php echo esc_html( $callback ); ?></code></p>
                    <p><strong>Webhook:</strong><br><code><?php echo esc_html( $webhook ); ?></code></p>
                </div>
            </div>
            <form method="post" action="options.php">
                <?php settings_fields( 'mirall_integrations' ); ?>
                <table class="form-table" role="presentation">
                    <tr><th>فعال‌سازی پرداخت آنلاین</th><td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[payment_enabled]" value="1" <?php checked( $s['payment_enabled'], '1' ); ?>> بعد از واردکردن اطلاعات واقعی درگاه فعال شود</label></td></tr>
                    <tr><th>Provider</th><td><select name="<?php echo esc_attr( self::OPTION ); ?>[provider]"><?php foreach ( [ 'custom' => 'API سفارشی', 'zarinpal' => 'زرین‌پال', 'idpay' => 'IDPay', 'manual' => 'پرداخت دستی / کارت‌به‌کارت' ] as $k => $v ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $s['provider'], $k ); ?>><?php echo esc_html( $v ); ?></option><?php endforeach; ?></select></td></tr>
                    <tr><th>Merchant ID</th><td><input class="regular-text" name="<?php echo esc_attr( self::OPTION ); ?>[merchant_id]" value="<?php echo esc_attr( $s['merchant_id'] ); ?>"></td></tr>
                    <tr><th>API Key</th><td><input class="regular-text" type="password" autocomplete="new-password" name="<?php echo esc_attr( self::OPTION ); ?>[api_key]" value="<?php echo esc_attr( $s['api_key'] ); ?>"></td></tr>
                    <tr><th>Request Endpoint</th><td><input class="large-text" type="url" name="<?php echo esc_attr( self::OPTION ); ?>[request_endpoint]" value="<?php echo esc_attr( $s['request_endpoint'] ); ?>"></td></tr>
                    <tr><th>Verify Endpoint</th><td><input class="large-text" type="url" name="<?php echo esc_attr( self::OPTION ); ?>[verify_endpoint]" value="<?php echo esc_attr( $s['verify_endpoint'] ); ?>"></td></tr>
                    <tr><th>Sandbox</th><td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[sandbox]" value="1" <?php checked( $s['sandbox'], '1' ); ?>> حالت آزمایشی</label></td></tr>
                    <tr><th>واحد پول</th><td><select name="<?php echo esc_attr( self::OPTION ); ?>[currency]"><option value="IRR" <?php selected( $s['currency'], 'IRR' ); ?>>ریال</option><option value="IRT" <?php selected( $s['currency'], 'IRT' ); ?>>تومان</option></select></td></tr>
                    <tr><th>مبلغ پیش‌فرض رزرو</th><td><input type="number" min="0" name="<?php echo esc_attr( self::OPTION ); ?>[default_amount]" value="<?php echo esc_attr( $s['default_amount'] ); ?>"></td></tr>
                    <tr><th>Webhook Secret</th><td><input class="regular-text" type="password" autocomplete="new-password" name="<?php echo esc_attr( self::OPTION ); ?>[webhook_secret]" value="<?php echo esc_attr( $s['webhook_secret'] ); ?>"></td></tr>
                    <tr><th>Callback URL</th><td><code><?php echo esc_html( $callback ); ?></code></td></tr>
                    <tr><th>Webhook URL</th><td><code><?php echo esc_html( $webhook ); ?></code></td></tr>
                </table>
                <?php submit_button( 'ذخیره تنظیمات اتصال' ); ?>
            </form>
        </div>
        <?php
    }

    public static function booking_meta_box() : void {
        add_meta_box( 'mirall_payment_details', 'پرداخت رزرو', [ __CLASS__, 'booking_meta_box_html' ], 'mirall_booking', 'side', 'high' );
    }

    public static function booking_meta_box_html( $post ) : void {
        wp_nonce_field( 'mirall_payment_meta', '_mirall_payment_nonce' );
        $s = self::settings();
        $amount = get_post_meta( $post->ID, '_mirall_booking_amount', true );
        $status = get_post_meta( $post->ID, '_mirall_payment_status', true ) ?: 'unpaid';
        $ref = get_post_meta( $post->ID, '_mirall_payment_ref', true );
        ?>
        <p><label><strong>مبلغ</strong><br><input style="width:100%" type="number" min="0" name="mirall_payment_amount" value="<?php echo esc_attr( $amount ?: $s['default_amount'] ); ?>"></label></p>
        <p><label><strong>وضعیت پرداخت</strong><br><select style="width:100%" name="mirall_payment_status"><?php foreach ( [ 'unpaid' => 'پرداخت نشده', 'pending' => 'در انتظار', 'paid' => 'پرداخت شده', 'failed' => 'ناموفق', 'refunded' => 'بازگشت وجه' ] as $k => $v ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $status, $k ); ?>><?php echo esc_html( $v ); ?></option><?php endforeach; ?></select></label></p>
        <p><label><strong>شماره مرجع</strong><br><input style="width:100%" name="mirall_payment_ref" value="<?php echo esc_attr( $ref ); ?>"></label></p>
        <?php
    }

    public static function save_booking_meta( int $post_id, $post ) : void {
        if ( ! isset( $_POST['_mirall_payment_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_mirall_payment_nonce'] ) ), 'mirall_payment_meta' ) ) { return; }
        if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }
        update_post_meta( $post_id, '_mirall_booking_amount', (string) max( 0, absint( $_POST['mirall_payment_amount'] ?? 0 ) ) );
        $status = sanitize_key( $_POST['mirall_payment_status'] ?? 'unpaid' );
        if ( ! in_array( $status, [ 'unpaid', 'pending', 'paid', 'failed', 'refunded' ], true ) ) { $status = 'unpaid'; }
        update_post_meta( $post_id, '_mirall_payment_status', $status );
        update_post_meta( $post_id, '_mirall_payment_ref', sanitize_text_field( $_POST['mirall_payment_ref'] ?? '' ) );
    }

    public static function ensure_payment_defaults( int $post_id, $post ) : void {
        if ( 'mirall_booking' !== $post->post_type ) { return; }
        $s = self::settings();
        if ( '' === (string) get_post_meta( $post_id, '_mirall_booking_amount', true ) ) { update_post_meta( $post_id, '_mirall_booking_amount', $s['default_amount'] ); }
        if ( '' === (string) get_post_meta( $post_id, '_mirall_payment_status', true ) ) { update_post_meta( $post_id, '_mirall_payment_status', 'unpaid' ); }
    }

    public static function enhance_account_shortcode( $output, $tag, $attr, $m ) {
        if ( 'mirall_account' !== $tag || ! is_user_logged_in() ) { return $output; }
        $bookings = get_posts( [
            'post_type' => 'mirall_booking',
            'post_status' => 'publish',
            'numberposts' => 20,
            'meta_key' => '_mirall_booking_user',
            'meta_value' => get_current_user_id(),
            'orderby' => 'date',
            'order' => 'DESC',
        ] );
        if ( ! $bookings ) { return $output; }
        $s = self::settings();
        ob_start(); ?>
        <section class="account-card mirall-payment-center" id="payments" style="margin-top:18px">
            <div class="account-card-head"><div><span class="eyebrow">PAYMENTS</span><h3>پرداخت‌های من</h3></div></div>
            <div class="booking-list">
                <?php foreach ( $bookings as $booking ) :
                    $status = get_post_meta( $booking->ID, '_mirall_payment_status', true ) ?: 'unpaid';
                    $amount = get_post_meta( $booking->ID, '_mirall_booking_amount', true ) ?: $s['default_amount'];
                    $service = get_post_meta( $booking->ID, '_mirall_booking_service', true ); ?>
                    <article><div><strong><?php echo esc_html( $service ?: 'رزرو میرال' ); ?></strong><span><?php echo esc_html( number_format_i18n( (int) $amount ) . ' ' . ( 'IRT' === $s['currency'] ? 'تومان' : 'ریال' ) ); ?></span></div><div class="booking-list-actions"><small><?php echo esc_html( self::payment_status_label( $status ) ); ?></small>
                    <?php if ( 'paid' !== $status && '1' === $s['payment_enabled'] ) : ?><a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mirall_payment_start&booking=' . $booking->ID ), 'mirall_pay_' . $booking->ID ) ); ?>">پرداخت آنلاین</a>
                    <?php elseif ( 'paid' === $status ) : ?><span>✓ پرداخت شده</span>
                    <?php else : ?><span>درگاه آماده اتصال</span><?php endif; ?></div></article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php return $output . ob_get_clean();
    }

    private static function payment_status_label( string $status ) : string {
        $labels = [ 'unpaid' => 'پرداخت نشده', 'pending' => 'در انتظار پرداخت', 'paid' => 'پرداخت شده', 'failed' => 'ناموفق', 'refunded' => 'بازگشت وجه' ];
        return $labels[ $status ] ?? 'پرداخت نشده';
    }

    public static function start_payment() : void {
        if ( ! is_user_logged_in() ) { auth_redirect(); }
        $booking_id = absint( $_GET['booking'] ?? 0 );
        if ( ! $booking_id || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'mirall_pay_' . $booking_id ) ) { wp_die( 'درخواست پرداخت معتبر نیست.' ); }
        $owner = (int) get_post_meta( $booking_id, '_mirall_booking_user', true );
        if ( $owner !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) { wp_die( 'اجازه دسترسی ندارید.' ); }
        $s = self::settings();
        $account = home_url( '/account/' );
        if ( '1' !== $s['payment_enabled'] ) { wp_safe_redirect( add_query_arg( 'payment', 'not-configured', $account ) ); exit; }
        if ( 'manual' === $s['provider'] ) { update_post_meta( $booking_id, '_mirall_payment_status', 'pending' ); wp_safe_redirect( add_query_arg( 'payment', 'manual', $account ) ); exit; }
        if ( empty( $s['request_endpoint'] ) || ( empty( $s['api_key'] ) && empty( $s['merchant_id'] ) ) ) { wp_safe_redirect( add_query_arg( 'payment', 'not-configured', $account ) ); exit; }
        $amount = (int) ( get_post_meta( $booking_id, '_mirall_booking_amount', true ) ?: $s['default_amount'] );
        $callback = add_query_arg( [ 'action' => 'mirall_payment_callback', 'booking' => $booking_id ], admin_url( 'admin-post.php' ) );
        $response = wp_remote_post( $s['request_endpoint'], [
            'timeout' => 20,
            'headers' => [ 'Content-Type' => 'application/json', 'Accept' => 'application/json' ],
            'body' => wp_json_encode( [
                'booking_id' => $booking_id, 'amount' => $amount, 'currency' => $s['currency'],
                'callback_url' => $callback, 'merchant_id' => $s['merchant_id'], 'api_key' => $s['api_key'],
                'sandbox' => '1' === $s['sandbox'], 'description' => 'Mirall Beauty booking #' . $booking_id,
            ] ),
        ] );
        if ( is_wp_error( $response ) ) { update_post_meta( $booking_id, '_mirall_payment_status', 'failed' ); wp_safe_redirect( add_query_arg( 'payment', 'failed', $account ) ); exit; }
        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        $redirect = '';
        foreach ( [ 'payment_url', 'redirect_url', 'url' ] as $key ) { if ( ! empty( $data[ $key ] ) ) { $redirect = esc_url_raw( $data[ $key ] ); break; } }
        if ( ! empty( $data['authority'] ) ) { update_post_meta( $booking_id, '_mirall_payment_authority', sanitize_text_field( $data['authority'] ) ); }
        update_post_meta( $booking_id, '_mirall_payment_status', 'pending' );
        if ( $redirect ) { wp_redirect( $redirect ); exit; }
        wp_safe_redirect( add_query_arg( 'payment', 'pending', $account ) ); exit;
    }

    public static function payment_callback() : void {
        $booking_id = absint( $_REQUEST['booking'] ?? 0 );
        if ( ! $booking_id ) { wp_die( 'رزرو نامعتبر است.' ); }
        $s = self::settings();
        $verified = false;
        $ref = sanitize_text_field( $_REQUEST['ref_id'] ?? $_REQUEST['track_id'] ?? $_REQUEST['reference'] ?? '' );
        if ( ! empty( $s['verify_endpoint'] ) ) {
            $response = wp_remote_post( $s['verify_endpoint'], [
                'timeout' => 20,
                'headers' => [ 'Content-Type' => 'application/json', 'Accept' => 'application/json' ],
                'body' => wp_json_encode( [
                    'booking_id' => $booking_id,
                    'amount' => (int) ( get_post_meta( $booking_id, '_mirall_booking_amount', true ) ?: $s['default_amount'] ),
                    'authority' => (string) get_post_meta( $booking_id, '_mirall_payment_authority', true ),
                    'merchant_id' => $s['merchant_id'], 'api_key' => $s['api_key'],
                    'callback_payload' => wp_unslash( $_REQUEST ),
                ] ),
            ] );
            if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) < 300 ) {
                $body = json_decode( wp_remote_retrieve_body( $response ), true );
                $verified = ! empty( $body['verified'] ) || in_array( strtolower( (string) ( $body['status'] ?? '' ) ), [ 'paid', 'success', 'successful', 'verified' ], true );
                if ( ! $ref ) { $ref = sanitize_text_field( $body['ref_id'] ?? $body['reference'] ?? '' ); }
            }
        } else {
            $verified = in_array( strtolower( sanitize_text_field( $_REQUEST['status'] ?? '' ) ), [ 'paid', 'success', 'successful', 'verified' ], true );
        }
        update_post_meta( $booking_id, '_mirall_payment_status', $verified ? 'paid' : 'failed' );
        if ( $ref ) { update_post_meta( $booking_id, '_mirall_payment_ref', $ref ); }
        wp_safe_redirect( add_query_arg( [ 'receipt' => $booking_id, 'payment' => $verified ? 'paid' : 'failed' ], home_url( '/account/' ) ) . '#mirall-receipt' ); exit;
    }

    public static function register_rest_routes() : void {
        register_rest_route( 'mirall/v1', '/payment/webhook', [
            'methods' => 'POST',
            'callback' => [ __CLASS__, 'webhook' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public static function webhook( WP_REST_Request $request ) {
        $s = self::settings();
        $configured = (string) $s['webhook_secret'];
        $provided = (string) $request->get_header( 'x-mirall-webhook-secret' );
        if ( ! $configured || ! $provided || ! hash_equals( $configured, $provided ) ) {
            return new WP_REST_Response( [ 'ok' => false, 'error' => 'unauthorized' ], 401 );
        }
        $booking_id = absint( $request->get_param( 'booking_id' ) );
        $status = sanitize_key( (string) $request->get_param( 'status' ) );
        $ref = sanitize_text_field( (string) $request->get_param( 'ref_id' ) );
        if ( ! $booking_id || 'mirall_booking' !== get_post_type( $booking_id ) ) { return new WP_REST_Response( [ 'ok' => false, 'error' => 'invalid_booking' ], 400 ); }
        if ( ! in_array( $status, [ 'unpaid', 'pending', 'paid', 'failed', 'refunded' ], true ) ) { return new WP_REST_Response( [ 'ok' => false, 'error' => 'invalid_status' ], 400 ); }
        update_post_meta( $booking_id, '_mirall_payment_status', $status );
        if ( $ref ) { update_post_meta( $booking_id, '_mirall_payment_ref', $ref ); }
        return [ 'ok' => true, 'booking_id' => $booking_id, 'status' => $status ];
    }
}

Mirall_Integrations::init();
