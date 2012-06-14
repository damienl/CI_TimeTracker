<?php
if ( !defined( 'BASEPATH' ) )
    exit( 'No direct script access allowed' );

class Timetracker_viz extends CI_Controller {
    function __construct( ) {
        parent::__construct();
        $this->output->enable_profiler( TRUE );

        $this->load->library( 'timetracker_lib' );

        $this->timetracker_lib->checkuser();
        $this->timetracker_lib->get_alerts();

    }





    /* ==========================
     *  actions
     * ========================== */


    public function summary( $username = NULL, $type_cat = 'categorie', $id = NULL, $date_plage = 'all' ) {

        //TODO add title and breadcrumb

        $this->timetracker_lib->checkUsername( $username );

        $tab = $this->input->get( 'tab', TRUE );
        if ( !in_array( $type_cat, array('activity','todo','value') ) ) {
            if ( $tab===FALSE ) $tab='activity';

            $this->data[ 'count' ][ 'activity' ]    = $this->records->get_records_count($this->user_id, $this->_getRecordsParam( $username, $type_cat, $id, 'activity' , $date_plage ) );
            $this->data[ 'count' ][ 'todo' ]        = $this->records->get_records_count($this->user_id, $this->_getRecordsParam( $username, $type_cat, $id, 'todo' , $date_plage ) );
            $this->data[ 'count' ][ 'value' ]       = $this->records->get_records_count($this->user_id, $this->_getRecordsParam( $username, $type_cat, $id, 'value' , $date_plage ) );
            $this->data[ 'tabs' ]               = tabs_buttons ( tt_url($username,'summary',$type_cat,$id, $date_plage), $this->data[ 'count' ], $tab );
        }


        $this->data['current']= array(
            "action" => 'summary',
            "type_cat" => $type_cat,
            "id" => $id,
            "date_plage" => $date_plage,
            "tab" => $tab
            );
        $this->data['records']= $this->_getRecords($username, $type_cat, $id, $tab , $date_plage);


        if ($type_cat=='categorie') {

            $this->data[ 'categorie' ] = $this->categories->get_categorie_by_id( $id );

            $this->data[ 'breadcrumb' ][]= array( 'title'=> 'categories', 'url'=>tt_url($username,'summary','categorie','all',$date_plage) );

            if ( $this->data[ 'categorie' ]['id']!=NULL) {
                if ( $this->data[ 'categorie' ]['title']=='')
                    $this->data[ 'breadcrumb' ][]= array( 'title'=> '_root_', 'url'=>tt_url($username,'summary','categorie',$id,  $date_plage) );
                else
                    $this->data[ 'breadcrumb' ][]= array( 'title'=> $this->data[ 'categorie' ]['title'], 'url'=>tt_url($username,'summary','categorie',$id,  $date_plage) );
            }

            $this->data[ 'title' ]='summary for categorie: '.$this->data[ 'categorie' ]['title'];
            if ( $this->data[ 'categorie' ]['title']=='') $this->data[ 'title' ].='_root_';

        }

        elseif ($type_cat=='tag'){

            $this->data[ 'tag' ] = $this->tags->get_tag_by_id( $id );

            $this->data[ 'breadcrumb' ][]= array( 'title'=> $this->data[ 'tag' ]['tag'], 'url'=>tt_url($username,'summary','tag',$this->data[ 'tag' ]['id'],  $date_plage) );

            $this->data[ 'title' ]='summary for tag : '.$this->data[ 'tag' ]['tag'];

        }

        elseif ($type_cat=='value_type'){

            $this->data[ 'value_type' ] = $this->values->get_valuetype_by_id( $id );

            $this->data[ 'breadcrumb' ][]= array( 'title'=> $this->data[ 'value_type' ]['title'], 'url'=>tt_url($username,'summary','value_type',$this->data[ 'value_type' ]['id'],  $date_plage) );

            $this->data[ 'title' ]='summary for value type : '.$this->data[ 'value_type' ]['title'];

        }

        else {

            $this->data[ 'activity' ] = $this->activities->get_activity_by_id_full( $id );

            if ( $this->data[ 'activity' ][ 'categorie' ]['title']!='')
                $this->data[ 'breadcrumb' ][]= array( 'title'=> $this->data[ 'activity' ][ 'categorie' ]['title'], 'url'=>tt_url($username,'summary','categorie',$this->data[ 'activity' ][ 'categorie' ]['id'],  $date_plage) );

            $this->data[ 'breadcrumb' ][]= array( 'title'=> $this->data[ 'activity' ]['title'], 'url'=>tt_url($username,'summary',$this->data[ 'activity' ]['type_of_record'],$this->data[ 'activity' ]['id'],  $date_plage) );

            $this->data[ 'title' ]='summary for '.$this->data[ 'activity' ]['type_of_record'].': '.$this->data[ 'activity' ]['title'];

        }



        if ($this->data['records']) {
            usort( $this->data['records'] , array("Timetracker_viz", "_orderByCat"));
            $this->data['stats']= $this->_getStats($this->data['records'], $type_cat,$this->data['dates']['min'],$this->data['dates']['max']);
        }


        $this->data[ 'tt_layout' ]          = 'tt_summary';


        $this->timetracker_lib->render();
    }



     public function graph( $username = NULL, $type_cat = 'categories', $id = NULL, $date_plage = 'all', $type_graph = 'histo' ) {

        $this->timetracker_lib->checkUsername( $username );

        $tab = $this->input->get( 'tab', TRUE );
        if ( !in_array( $type_cat, array('activity','todo','value') ))
            if ( $tab===FALSE ) $tab='activity';

         $groupby = $this->input->get( 'groupby', TRUE );
            if ( $groupby===FALSE ) $groupby='day';


        $this->data['current']= array(
            "action" => 'graph',
            "type_cat"=>$type_cat,
            "id"=>$id,
            "date_plage"=>$date_plage,
            "tab" => $tab,
            "type_graph"=>$type_graph,
            "group_by"=>$groupby
            );

        $this->data['datagraph']= $this->data['current'];
        unset($this->data['datagraph']["action"]);
        $this->data['datagraph']['username']=$username;

        $this->data['records']= $this->_getRecords($username, $type_cat, $id, $tab, $date_plage); // todo virer

        $this->data[ 'tt_layout' ]          = 'tt_graph';
        $this->timetracker_lib->render();
    }



    public function export( $username = NULL, $type_cat = 'categories', $id = NULL, $date_plage = 'all', $format = 'json' ) {

        $this->load->helper('download');
        $this->timetracker_lib->checkUsername( $username );

        $this->data['current']= array(
            "action" => 'export',
            "type_cat"=>$type_cat,
            "id"=>$id,
            "date_plage"=>$date_plage
            );

        $records= $this->_getRecords($username, $type_cat, $id, NULL, $date_plage);

        $this->output->enable_profiler( FALSE );

        // TODO modif entetes


        if ($format == 'csv') { // use content_output
            $content = '"id","start_time","duration","stop_at","trim_duration","running","title","activity_ID","categorie_ID","type_of_record","tags","value","description"'."\r\n";
            foreach ( $records as $k => $record )
                $content .= str_replace( array("\r","\n"), " ", $record['id'].',"'.$record['start_time'].'",'.$record['duration'].',"'.$record['stop_at'].'",'.@$record['trim_duration'].','.$record['running'].',"'.$record['activity']['activity_path'].'",'.$record['activity_ID'].','.$record['categorie_ID'].',"'.$record['activity']['type_of_record'].'","'.@$record['tags_path'].'","'.@$record['value']['value_path'].'","'.$record['description'].'"')."\r\n";

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

            if ($records) {
                usort($records , array("Timetracker_viz", "_orderByCat"));
                $stats= $this->_getStats($records, $type_cat,$this->data['dates']['min'],$this->data['dates']['max']);
            }

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



// JSON Graphs

 public function histo_json( $username = NULL, $type_cat = 'categories', $id = NULL, $date_plage = 'all', $group_by='day' ) {

     // basé sur unix time donc ne tiens pas compte des fuseaux :( TODO
     // change to MySQL date

        $this->load->helper('download');
        $this->timetracker_lib->checkUsername( $username );

        $records= $this->_getRecords(   $username, $type_cat, $id, 'activity', $date_plage);
        $param=$this->_getRecordsParam( $username, $type_cat, $id, 'activity' , $date_plage );

        $this->output->enable_profiler( FALSE );

        $date_plage_array= $this->_getDatePlage($date_plage);


        if ($date_plage_array['min']==NULL) $date_plage_array['min']= $this->records->get_min_time($this->user_id, $param);
        if ($date_plage_array['max']==NULL) $date_plage_array['max']= $this->records->get_max_time($this->user_id, $param);



        switch ($group_by) {
            case 'minute':
                $timelapse=array( 60 ,$group_by);
                break;
            case 'hour':
                $timelapse=array( 60*60 ,$group_by);
                break;
            case 'day':
                $timelapse=array( 60*60*24 ,$group_by);
                break;
            case 'week':
                $timelapse=array( 60*60*24*7 ,$group_by);
                break;
        }

       // $date_plage_array['max']= date( 'Y-m-d H:i:s', strtotime( $date_plage_array['max'] )+ $timelapse[0] );

        $data=array(
            'min'=>$this->_get_time_before($date_plage_array['min'],$timelapse),
            'times'=> array()
            );

        if ($date_plage_array['max']==='running') {
            $data['running']=TRUE;
            $data['max']=$this->_get_time_before($this->server_time,$timelapse);
        }
        else
        {
            $data['running']=FALSE;
            $data['max']=$this->_get_time_before($date_plage_array['max'],$timelapse);
        }


        for ($t=strtotime($data['min']); $t<=strtotime($data['max']); $t+=$timelapse[0]) {
            $rec=array( 'time'=>date( 'Y-m-d H:i:s', $t), 'total'=>0, 'activities'=>array() );
            $activities=array();

            // add activities
            foreach ( $records as $k => $record ) {
               $record['trim_duration']= $this->records->trim_duration($record, $t, $t+$timelapse[0] );
              if ($record['trim_duration']>0) {
                  if (!isset($activities[$record['activity']['id']])) $activities[$record['activity']['id']]= array('duration'=>0, 'activity'=>$record['activity']['activity_path'], 'activity_ID'=>$record['activity']['id'] );
                 $activities[$record['activity']['id']]['duration']+= $record['trim_duration'];
                 $rec['total']+=$record['trim_duration'];
              }
            }

            //sort($activities);
            foreach ( $activities as $k => $activity ) $rec['activities'][]=$activity;


            $data['times'][]=$rec;
        }



         $content= json_encode($data,JSON_NUMERIC_CHECK);
            $this->output
            ->set_content_type('application/json')
            ->set_output( $content );
    }



// TOOLS

    private function _get_time_before($date,$timelapse) {

       /* if ($timelapse[0]=='week') {
            TODO gestion semaine avec prise en compte du decalage horaire
        }*/


        $d= new DateTime($date);
       /* print_r($date);
        print_r($d);
        print_r($d->format('Y-m-d H:i:s'));
        * TODO
        */

     return $d->format('Y-m-d H:i:s');
        }

    private function _getDatePlage($date_plage) {

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



    private function _getRecords( $username, $type_cat, $id, $type_of_record , $date_plage ) {

        $param=$this->_getRecordsParam( $username, $type_cat, $id, $type_of_record , $date_plage );

        $res= $this->records->get_records_full($this->user_id, $param);

        return $res;
    }



    private function _getRecordsParam( $username, $type_cat, $id, $type_of_record , $date_plage ) {
        $param=array('order'=>'ASC');

        if ($id=='all') $id=NULL;

         $param['type_of_record']=$type_of_record;


        if ($type_cat=='categorie') $param['categorie']=$id;

        if ($type_cat=='activity')  { $param['activity']=$id; $param['type_of_record']='activity'; }
        if ($type_cat=='todo')      { $param['activity']=$id; $param['type_of_record']='todo'; }
        if ($type_cat=='value')     { $param['activity']=$id; $param['type_of_record']='value'; }

        if ($type_cat=='tag')       $param['tags']= array( $id );
        if ($type_cat=='valuetype') $param['valuetype']=  $id;




            $date_array=$this->_getDatePlage($date_plage);
            $this->data['dates']= $date_array;
            $param['datemin'] = $date_array['min'];
            $param['datemax'] = $date_array['max'];




        $param['order']='ASC';

        return $param;
    }



    private function _orderByCat( $a,$b ) {
        return ($a['activity']['categorie']['title'] < $b['activity']['categorie']['title']) ? -1 : 1;
    }






    private function _getStats($records, $type_cat, $datemin=NULL, $datemax=NULL) {
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
             if (!isset( $res[ 'categorie' ][ $record['activity']['type_of_record'] ][ $record['activity']['categorie']['id'] ] )) {
                    $res[ 'categorie' ][ $record['activity']['type_of_record'] ][ $record['activity']['categorie']['id'] ]= $record['activity']['categorie'];
                    $res[ 'categorie' ][ $record['activity']['type_of_record'] ][ $record['activity']['categorie']['id'] ]['count'] = 0;
                    $res[ 'categorie' ][ $record['activity']['type_of_record'] ][ $record['activity']['categorie']['id'] ]['total'] = 0;
             }

             $res[ 'categorie' ][ $record['activity']['type_of_record'] ][ $record['activity']['categorie']['id'] ]['count'] ++;
             $res[ 'categorie' ][ $record['activity']['type_of_record'] ][ $record['activity']['categorie']['id'] ]['total'] += $duration;

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
