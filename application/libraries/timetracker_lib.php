<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Timetracker_lib
{


    function __construct()
    {
        $this->ci =& get_instance();

    }



    function checkuser()
    {

        if ( !$this->ci->tank_auth->is_logged_in() ) $this->goLogin();

        $this->ci->user_id   = $this->ci->tank_auth->get_user_id();
        $this->ci->user_name = $this->ci->tank_auth->get_username();
        $this->ci->user_profile = $this->ci->tank_auth->get_profile();

        $this->ci->user_params = json_decode( $this->ci->user_profile['params'], true );

        $this->ci->data[ 'user_name' ] =        $this->ci->user_name;
        $this->ci->data[ 'user_id' ]   =        $this->ci->user_id;
        $this->ci->data[ 'user_params' ]   =    $this->ci->user_params;

        // SET MySQL timezone to user timezone;
        $this->ci->db->query( "SET time_zone= '".$this->ci->user_profile['timezone']."'" );


    }



    function goLogin( ) {
        redirect( 'login', 'location', 301 );
    }


    function checkUsername( $username ) {
        if ( $username != $this->ci->data[ 'user_name' ] )
            $this->goLogin(); //TODO shared folder gestion
    }


    function get_alerts(){
        $this->ci->data[ 'alerts' ] = array( );
        if ( $this->ci->session->flashdata( 'alerts' ) )
            $this->ci->data[ 'alerts' ] = $this->ci->session->flashdata( 'alerts' ); //array( array('type'=>'success', 'alert'=>'error 1 .....') );

    }

}

/* End of file Tank_auth.php */
/* Location: ./application/libraries/Tank_auth.php */