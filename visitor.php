<?php
 
namespace MyListing\Src;
 
class Visitor {
    use \MyListing\Src\Traits\Instantiatable;
 
    public
        $ip = null,
        $language = null,
        $referrer = null,
        $fingerprint = null;
 
    public function __construct() {
        //
    }
 
    /**
     * Get user IP address if available in $_SERVER.
     *
     * @since 2.0
     */
    public function get_ip() {
        $ip = false;
        $keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];
 
        foreach ( $keys as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $ip = trim( $_SERVER[ $key ] );
                break;
            }
        }
 
        if ( $ip ) {
            // make sure it's a valid ip address
            if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                return $ip;
            }
 
            // sometimes multiple ip's are returned in comma-separated format
            $ips = explode( ',', $ip );
            $first_ip = trim( $ips[0] );
            if ( filter_var( $first_ip, FILTER_VALIDATE_IP ) ) {
                return $first_ip;
            }
        }
 
        return false;
    }
 
    /**
     * Get visitor's browser language code
     * based on $_SERVER['HTTP_ACCEPT_LANGUAGE']
     *
     * @link  https://www.dyeager.org/2008/10/getting-browser-default-language-php.html
     * @since 2.0
     */
    public function get_language( $default = 'en') {
        if ( empty( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
            return false;
        }
 
        // Split possible languages into array
        $x = explode( ',', $_SERVER['HTTP_ACCEPT_LANGUAGE'] );
        foreach ( $x as $val ) {
            // check for q-value and create associative array. No q-value means 1 by rule
            if ( preg_match( "/(.*);q=([0-1]{0,1}.\d{0,4})/i", $val, $matches ) ) {
                $lang[$matches[1]] = (float) $matches[2];
            } else {
                $lang[$val] = 1.0;
            }
        }
 
        // return default language (highest q-value)
        $qval = 0.0;
        foreach ( $lang as $key => $value ) {
            if ( $value > $qval ) {
                $qval = (float) $value;
                $default = $key;
            }
        }
 
        return $default;
    }
 
    /**
     * Get referrer URL if available in $_SERVER.
     *
     * @since 2.0
     */
    public function get_referrer() {
        if ( empty( $_SERVER['HTTP_REFERER'] ) ) {
            return false;
        }
 
        $url = $_SERVER['HTTP_REFERER'];
        $parts = parse_url( $url );
 
        if ( $parts === false || empty( $parts['host'] ) ) {
            return false;
        }
 
        return [
            'url' => $url,
            'domain' => $parts['host'],
        ];
    }
 
    /**
     * Get user OS info based on $_SERVER['HTTP_USER_AGENT'].
     *
     * @link  https://stackoverflow.com/a/15497878/3522553
     * @since 2.0
     */
    public function get_os() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $device = wp_is_mobile() ? 'mobile' : 'desktop';
        $os_platform = false;
        $os_array = [
            '/windows nt 10/i'                 =>  'Windows 10',
            '/windows nt 6.2|windows nt 6.3/i' =>  'Windows 8',
            '/windows nt 6.1/i'                =>  'Windows 7',
            '/macintosh|mac os x/i'            =>  'macOS',
            '/linux/i'                         =>  'Linux',
            '/ubuntu/i'                        =>  'Ubuntu',
            '/iphone|ipad|ipod/i'              =>  'iOS',
            '/android/i'                       =>  'Android',
            '/webos/i'                         =>  'webOS'
        ];
 
        foreach ( $os_array as $regex => $value ) {
            if ( preg_match( $regex, $user_agent ) ) {
                $os_platform = $value;
            }
        }
 
        return [
            'os' => $os_platform,
            'device' => $device,
        ];
    }
 
    /**
     * Get user browser info based on $_SERVER['HTTP_USER_AGENT'].
     *
     * @link  https://stackoverflow.com/a/15497878/3522553
     * @since 2.0
     */
    public function get_browser() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $browser = false;
        $browser_array  = [
            '/msie/i'    =>  'Internet Explorer',
            '/firefox/i' =>  'Firefox',
            '/safari/i'  =>  'Safari',
            '/chrome/i'  =>  'Chrome',
            '/edge/i'    =>  'Edge',
            '/opera/i'   =>  'Opera',
            '/mobile/i'  =>  'Handheld Browser'
        ];
 
        foreach ( $browser_array as $regex => $value ) {
            if ( preg_match( $regex, $user_agent ) ) {
                $browser = $value;
            }
        }
 
        return $browser;
    }
 
    public function get_user_agent() {
        return $_SERVER['HTTP_USER_AGENT'];
    }
 
    /**
     * Get user country and city if available
     * through IP-based geolocation.
     *
     * @link  https://geoip.nekudo.com/
     * @since 2.0
     */
    public function get_location() {
        // If it's available in session, get location data from there.
        $cookie = mylisting()->cookies()->get( md5( 'mylisting_visitor_location' ) );
        if ( ! empty( $cookie ) ) {
            // mlog( 'Retrieved user location from cookie (IP geolocation).'."[$cookie]" );
            return $cookie;
        }
 
        if ( ! ( $ip_address = $this->get_ip() ) ) {
            return false;
        }
 
        if ( ! class_exists( '\WC_Geolocation' ) || apply_filters( 'mylisting/stats/disable-ip-geolocation', false ) === true ) {
            return false;
        }
 
        $response = \WC_Geolocation::geolocate_ip( $ip_address );
        $location = $response['country'];
 
        // Store location in cookie, so we don't have to request it on every page visit.
        mylisting()->cookies()->set( md5( 'mylisting_visitor_location' ), $location, time() + DAY_IN_SECONDS );
 
        mlog()->warn( 'Retrieved user location from query. (IP geolocation).'."[$location]" );
        return $location;
    }
 
    /**
     * Generate a fingerprint from available data,
     * to identify unique visitors.
     *
     * @since 2.0
     */
    public function get_fingerprint() {
        return md5( json_encode( [
            $this->get_ip(),
            $this->get_language(),
            $this->get_os(),
            $this->get_browser(),
        ] ) );
    }
}
