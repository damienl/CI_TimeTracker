<?php
if ( !defined( 'BASEPATH' ) )
    exit( 'No direct script access allowed' );

class Timetracker_viz extends CI_Controller {
    function __construct( ) {
        parent::__construct();
        $this->output->enable_profiler( TRUE );

        $this->load->helper( array(
             'url',
            'assets_helper',
            'form',
            'timetracker',
            'date',
            'array'
        ) );

        $this->load->library( 'tank_auth' );

        $this->load->model( array(
            'timetracker/categories',
            'timetracker/activities',
            'timetracker/tags',
            'timetracker/values',
            'timetracker/records'
        ) );

        $this->user_id   = $this->tank_auth->get_user_id();
        $this->user_name = $this->tank_auth->get_username();

        $this->data[ 'alerts' ] = array( );


        if ( $this->session->flashdata( 'alerts' ) )
            $this->data[ 'alerts' ] = $this->session->flashdata( 'alerts' ); //array( array('type'=>'success', 'alert'=>'error 1 .....') );


        if ( !$this->tank_auth->is_logged_in() ) {
            $this->_goLogin();
        }
        else {
            $this->data[ 'user_name' ] = $this->user_name;
            $this->data[ 'user_id' ]   = $this->user_id;
        }

    }




    /* ==========================
     *  rendering & redirection
     * ========================== */

    public function _render( ) {
        $this->data[ 'content' ] = $this->load->view( 'timetracker/layout', $this->data, true );
        $this->load->view( 'layout', $this->data );
    }

    public function _goLogin( ) {
        redirect( 'login', 'location', 301 );
    }

    public function _checkUsername( $username ) {
        if ( $username != $this->data[ 'user_name' ] )
            $this->_goLogin(); //TODO shared folder gestion
    }




    /* ==========================
     *  actions
     * ========================== */


    public function summary( $username = NULL, $type_cat = 'categories', $id = NULL, $date_plage = NULL ) {

        $this->_checkUsername( $username );

        $this->data['records']= $this->_getRecords($username, $type_cat, $id, $date_plage);

        if ($this->data['records'])
            $this->data['stats']= $this->_getStats($this->data['records'], $type_cat);


        $this->data[ 'tt_layout' ]          = 'tt_summary';

        $this->_render();
    }



    public function export( $username = NULL, $type_cat = 'categories', $id = NULL, $date_plage = NULL, $format = 'json' ) {

        $this->_checkUsername( $username );

        $records= $this->_getRecords($username, $type_cat, $id, $date_plage);

        $this->output->enable_profiler( FALSE );

        // TODO modif entetes


       if ($format == 'csv') echo 'csv';

       if ($format == 'json') echo json_encode($records,JSON_NUMERIC_CHECK);

       if ($format == 'txt') echo 'txt';
    }



    public function _getDatePlage($date_plage) {

        $d1=$d2=NULL;
        $type='all';

        $sp= preg_split("/_/",$date_plage);

        if (count($sp)!=2) return array(NULL,NULL,'all');

        if ( $sp[1] == 'Y' ) {
            $d1 =  new DateTime( $sp[0].'-01-01');
            $d2 =  new DateTime( $sp[0].'-01-01');
            $d2->add( new DateInterval( 'P1Y' ) );
            $type='year';
            }

       elseif ( $sp[1] == 'M' )  {
            $d1 =  new DateTime( $sp[0].'-01');
            $d2 =  new DateTime( $sp[0].'-01');
            $d2->add( new DateInterval( 'P1M' ) );
            $type='month';
            }

        elseif ( $sp[1] == 'W' )  {
            $d1 =  new DateTime( $sp[0]);
            $d2 =  new DateTime( $sp[0]);
            $d2->add( new DateInterval( 'P1W' ) );
            $type='week';
            }

        elseif ( $sp[1] == 'D' )  {
            $d1 =  new DateTime( $sp[0]);
            $d2 =  new DateTime( $sp[0]);
            $d2->add( new DateInterval( 'P1D' ) );
            $type='day';
            }

        else {
            $d1 =  new DateTime( $sp[0]);
            $d2 =  new DateTime( $sp[1]);
            $type='manual';
            }

        return array( $d1->format('Y-m-d H:i:s'), $d2->format('Y-m-d H:i:s'), $type );
    }



    public function _getRecords( $username, $type_cat, $id, $date_plage ) {
        $param=array('order'=>'ASC');


        if ($type_cat=='categorie') $param['categorie_id']=$id;
        if ($type_cat=='activity')  $param['activity_id']=$id;
        if ($type_cat=='tag')       $param['tags']= array( $id );
        if ($type_cat=='valuetype') $param['valuetype']=  $id;




            $date_array=$this->_getDatePlage($date_plage);
            $this->data['dates']= $date_array;
            $param['datemin'] = $date_array[0];
            $param['datemax'] = $date_array[1];




        $param['order']='ASC';

        return $this->records->get_records_full($this->user_id, $param);
    }




    public function _getStats($records, $type_cat, $datemin=NULL, $datemax=NULL) {
        $res = array( );

        //TODO couper les duree en fonction datemin max et pour les runnings
        foreach ($records as $k => $record ) {

            if (!isset(  $res[ $record['activity']['type_of_record'].'_total'] ))  $res[ $record['activity']['type_of_record'].'_total']=0;
            if (!isset(  $res[ $record['activity']['type_of_record'].'_count'] ))  $res[ $record['activity']['type_of_record'].'_count']=0;

            $res[ $record['activity']['type_of_record'].'_total'] += $record['duration'];
            $res[ $record['activity']['type_of_record'].'_count'] ++;


             if (!isset( $res[ $record['activity']['type_of_record'] ][ $record['activity']['id'] ] )) {
                    $res[ $record['activity']['type_of_record'] ][ $record['activity']['id'] ]= $record['activity'];
                    $res[ $record['activity']['type_of_record'] ][ $record['activity']['id'] ]['count'] = 0;
                    $res[ $record['activity']['type_of_record'] ][ $record['activity']['id'] ]['total'] = 0;
             }

             $res[ $record['activity']['type_of_record'] ][ $record['activity']['id'] ]['count'] ++;
             $res[ $record['activity']['type_of_record'] ][ $record['activity']['id'] ]['total'] += $record['duration'];

            if (isset($record['tags']))
            foreach ($record['tags'] as $kt => $tag) {

                if (!isset(  $res[ $record['activity']['type_of_record'].'_tag_total'] ))  $res[ $record['activity']['type_of_record'].'_tag_total']=0;
                if (!isset(  $res[ $record['activity']['type_of_record'].'_tag_count'] ))  $res[ $record['activity']['type_of_record'].'_tag_count']=0;

                $res[ $record['activity']['type_of_record'].'_tag_total'] += $record['duration'];
                $res[ $record['activity']['type_of_record'].'_tag_count'] ++;

                if (!isset( $res[ $record['activity']['type_of_record'].'_tag' ][ $tag['id'] ] )) {
                    $res[ $record['activity']['type_of_record'].'_tag' ][ $tag['id'] ]['tag']= $tag['tag'];
                    $res[ $record['activity']['type_of_record'].'_tag' ][ $tag['id'] ]['count'] = 0;
                    $res[ $record['activity']['type_of_record'].'_tag' ][ $tag['id'] ]['total'] = 0;
                    }

                $res[ $record['activity']['type_of_record'].'_tag' ][ $tag['id'] ]['count'] ++;
                $res[ $record['activity']['type_of_record'].'_tag' ][ $tag['id'] ]['total'] += $record['duration'];

            }




        } // end foreach


        return $res;
    }



}

/* End of file test.php */
/* Location: ./application/controllers/timetracker_viz.php */
