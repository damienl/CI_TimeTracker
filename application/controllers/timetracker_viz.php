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


    public function summary( $username = NULL, $type_cat = 'categories', $id = NULL, $date_plage = 'all' ) {

        $this->_checkUsername( $username );

        $this->data['current']= array(
            "type_cat"=>$type_cat,
            "id"=>$id,
            "date_plage"=>$date_plage
            );
        $this->data['records']= $this->_getRecords($username, $type_cat, $id, $date_plage);

        if ($this->data['records']) {
            usort( $this->data['records'] , array("Timetracker_viz", "_orderByCat"));
            $this->data['stats']= $this->_getStats($this->data['records'], $type_cat,$this->data['dates']['min'],$this->data['dates']['max']);
        }


        $this->data[ 'tt_layout' ]          = 'tt_summary';


        $this->_render();
    }



    public function export( $username = NULL, $type_cat = 'categories', $id = NULL, $date_plage = NULL, $format = 'json' ) {

        $this->load->helper('download');
        $this->_checkUsername( $username );

        $records= $this->_getRecords($username, $type_cat, $id, $date_plage);

        if ($records) {
            usort($records , array("Timetracker_viz", "_orderByCat"));
            $stats= $this->_getStats($records, $type_cat,$this->data['dates']['min'],$this->data['dates']['max']);
        }

        $this->output->enable_profiler( FALSE );

        // TODO modif entetes


        if ($format == 'csv') { // use content_output
            $content = '"id","start_time","diff_greenwich","duration","stop_at","trim_duration","running","title","activity_ID","categorie_ID","type_of_record","tags","value","description"'."\r\n";
            foreach ( $records as $k => $record )
                $content .= str_replace( array("\r","\n"), " ", $record['id'].',"'.$record['start_time'].'",'.$record['diff_greenwich'].','.$record['duration'].',"'.$record['stop_at'].'",'.@$record['trim_duration'].','.$record['running'].',"'.$record['activity']['activity_path'].'",'.$record['activity_ID'].','.$record['categorie_ID'].',"'.$record['activity']['type_of_record'].'","'.@$record['tags_path'].'","'.@$record['value']['value_path'].'","'.$record['description'].'"')."\r\n";

            $this->output
                ->set_content_type('text/csv');
            force_download("tt_".$username."_ci.csv", $content);

        }

        if ($format == 'json') {
            $content= json_encode($records,JSON_NUMERIC_CHECK);
            $this->output
            ->set_content_type('application/json')
            ->set_output( $content );
        }


         if ($format == 'txt') { // use content_output

            $content= draw_text_table($records);


            //STATS

            if (isset($stats['categorie']))  $content .=  "\r\n\r\ncategories\r\n".draw_text_table($stats['categorie']);

            if (isset($stats['activity']))  $content .=  "\r\n\r\nactivities\r\n".draw_text_table($stats['activity']);
            if (isset($stats['activity_tag']))  $content .=  "\r\n\r\nactivities tags\r\n".draw_text_table($stats['activity_tag']);

            if (isset($stats['todo']))  $content .=  "\r\n\r\ntodos\r\n".draw_text_table($stats['todo']);
            if (isset($stats['todo_tag']))  $content .=  "\r\n\r\ntodos tags\r\n".draw_text_table($stats['todo_tag']);

            if (isset($stats['value']))  $content .=  "\r\n\r\nvalues\r\n".draw_text_table($stats['value']);
            if (isset($stats['value_tag']))  $content .=  "\r\n\r\nvalues tags\r\n".draw_text_table($stats['value_tag']);


            $this->output
                ->set_content_type('text/txt')
                ->set_output( $content );
            force_download("tt_".$username."_ci.txt", $content);

             //TODO add date and select params to json
        }


    }



    public function _getDatePlage($date_plage) {

        $d1=$d2=NULL;
        $type='all';

        $sp= preg_split("/_/",$date_plage);

        if (count($sp)!=2) return  array( 'min'=> NULL, 'max' => NULL, 'type'=> 'all', 'uri' => $date_plage);;

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

        return array( 'min'=> $d1->format('Y-m-d H:i:s'), 'max' => $d2->format('Y-m-d H:i:s'), 'type'=> $type, 'uri' => $date_plage);
    }



    public function _getRecords( $username, $type_cat, $id, $date_plage ) {
        $param=array('order'=>'ASC');


        if ($type_cat=='categorie') $param['categorie_id']=$id;
        if ($type_cat=='activity')  $param['activity_id']=$id;
        if ($type_cat=='tag')       $param['tags']= array( $id );
        if ($type_cat=='valuetype') $param['valuetype']=  $id;




            $date_array=$this->_getDatePlage($date_plage);
            $this->data['dates']= $date_array;
            $param['datemin'] = $date_array['min'];
            $param['datemax'] = $date_array['max'];




        $param['order']='ASC';

        $res= $this->records->get_records_full($this->user_id, $param);

        return $res;
    }



    public function _orderByCat( $a,$b ) {
        return ($a['activity']['categorie']['title'] < $b['activity']['categorie']['title']) ? -1 : 1;
    }






    public function _getStats($records, $type_cat, $datemin=NULL, $datemax=NULL) {
        $res = array( );

        //TODO couper les duree en fonction datemin max et pour les runnings
        foreach ($records as $k => $record ) {

            if (isset($record['trimmed_duration'])) $duration=$record['trimmed_duration'];
                else $duration=$record['duration'];



            if (!isset(  $res[ $record['activity']['type_of_record'].'_total'] ))  $res[ $record['activity']['type_of_record'].'_total']=0;
            if (!isset(  $res[ $record['activity']['type_of_record'].'_count'] ))  $res[ $record['activity']['type_of_record'].'_count']=0;

            $res[ $record['activity']['type_of_record'].'_total'] += $duration;
            $res[ $record['activity']['type_of_record'].'_count'] ++;

            // stat categorie
             if (!isset( $res[ 'categorie' ][ $record['activity']['categorie']['id'] ] )) {
                    $res[ 'categorie' ][ $record['activity']['categorie']['id'] ]= $record['activity']['categorie'];
                    $res[ 'categorie' ][ $record['activity']['categorie']['id'] ]['count'] = 0;
                    $res[ 'categorie' ][ $record['activity']['categorie']['id'] ]['total'] = 0;
             }

             $res[ 'categorie' ][ $record['activity']['categorie']['id'] ]['count'] ++;
             $res[ 'categorie' ][ $record['activity']['categorie']['id'] ]['total'] += $duration;

            // stat activity
             if (!isset( $res[ $record['activity']['type_of_record'] ][ $record['activity']['id'] ] )) {
                    $res[ $record['activity']['type_of_record'] ][ $record['activity']['id'] ]= $record['activity'];
                    $res[ $record['activity']['type_of_record'] ][ $record['activity']['id'] ]['count'] = 0;
                    $res[ $record['activity']['type_of_record'] ][ $record['activity']['id'] ]['total'] = 0;
             }

             $res[ $record['activity']['type_of_record'] ][ $record['activity']['id'] ]['count'] ++;
             $res[ $record['activity']['type_of_record'] ][ $record['activity']['id'] ]['total'] += $duration;


            // stat activity
            if (isset($record['tags']))
            foreach ($record['tags'] as $kt => $tag) {

                if (!isset(  $res[ $record['activity']['type_of_record'].'_tag_total'] ))  $res[ $record['activity']['type_of_record'].'_tag_total']=0;
                if (!isset(  $res[ $record['activity']['type_of_record'].'_tag_count'] ))  $res[ $record['activity']['type_of_record'].'_tag_count']=0;

                $res[ $record['activity']['type_of_record'].'_tag_total'] += $duration;
                $res[ $record['activity']['type_of_record'].'_tag_count'] ++;

                if (!isset( $res[ $record['activity']['type_of_record'].'_tag' ][ $tag['id'] ] )) {
                    $res[ $record['activity']['type_of_record'].'_tag' ][ $tag['id'] ]['tag']= $tag['tag'];
                    $res[ $record['activity']['type_of_record'].'_tag' ][ $tag['id'] ]['count'] = 0;
                    $res[ $record['activity']['type_of_record'].'_tag' ][ $tag['id'] ]['total'] = 0;
                    }

                $res[ $record['activity']['type_of_record'].'_tag' ][ $tag['id'] ]['count'] ++;
                $res[ $record['activity']['type_of_record'].'_tag' ][ $tag['id'] ]['total'] += $duration;

            }




        } // end foreach


        return $res;
    }



}

/* End of file test.php */
/* Location: ./application/controllers/timetracker_viz.php */
