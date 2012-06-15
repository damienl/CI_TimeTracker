<?php
if ( !defined( 'BASEPATH' ) )
    exit( 'No direct script access allowed' );

class Records extends CI_Model {
    private $activities_table = 'activities';
    private $categories_table = 'categories';
    private $records_table = 'records';
    private $records_values_table = 'l_records_values';
    private $records_tags_table = 'l_records_tags';



    /**
     * Get record by Id
     *
     * @record_id     int
     * @return          array
     */
    function get_record_by_id( $record_id ) {


        $this->db->select($this->records_table . '.*, UNIX_TIMESTAMP(' . $this->records_table . '.start_time) as UNIXTIME');
        $this->db->from($this->records_table);
        $this->db->where( 'id', $record_id );

        $query = $this->db->get();


        /*  $this->db->select($this->activities_table.'.title,type_of_record,categorie_ID,'.$this->records_table.'.*');
        $this->db->from($this->records_table);
        $this->db->join($this->activities_table, $this->activities_table.'.id = '. $this->records_table.'.activity_ID');
        $this->db->where($this->records_table.'.id',$record_id);

        $query = $this->db->get();*/

        if ( $query->num_rows() >= 1 )
            return $query->row_array();
        return NULL;
    }



    /**
     * Create new record
     *
     * @activity_id     int
     * @param           string
     */
    function create_record( $activity_id, $param ) {
        $param = array_merge( $param, array(
             'activity_ID' => $activity_id
        ) );

        if ( $this->db->insert( $this->records_table, $param ) ) {
            $data = $this->get_record_by_id( $this->db->insert_id() );
            return $data;
        }
        return NULL;
    }






    /**
     * Update record
     *
     * @record_id       int
     * @title           string
     * @return          boolean
     */
    function update_record( $record_id, $param ) {
        $this->db->where( 'id', $record_id );

        if ( $this->db->update( $this->records_table, $param ) )
            return TRUE;

        return FALSE;
    }




    /**
     * delete record
     *
     * @record_id       int
     * @return          boolean
     */
    function delete_record( $record_id ) {
        $this->db->where( 'id', $record_id );

        if ( $this->db->delete( $this->records_table ) )
            return TRUE;

        return FALSE;
    }



    /* ===========
     * TOOLS
     * ===========*/


    function get_records($user_id, $param = array(), $offset= NULL, $count= NULL ) {

       /* $req = 'SET time_zone = "+6:00"';
        $this->db->query( $req );*/
        $req = 'SELECT ' . $this->activities_table . '.title,type_of_record,categorie_ID,' . $this->records_table . '.*,UNIX_TIMESTAMP(' . $this->records_table . '.start_time) as UNIXTIME
            '.$this->param2fromwhere($user_id, $param);


        $query = $this->db->query( $req );

        if ( $query->num_rows() >= 1 ) {
            $res=$query->result_array();

            if ((isset( $param['datemin'] ))&&(isset( $param['datemax'] )))
                foreach ( $res as $k => $item ) $res[$k]['trimmed_duration']= $this->trim_duration($item, strtotime($param['datemin']), strtotime($param['datemax']) );

            return $res;
        }

        return NULL;
    }




    function get_records_count($user_id, $param = array() ) {

        $req = 'SELECT  count(' . $this->records_table . '.id) as count
             '.$this->param2fromwhere($user_id, $param);

        $query = $this->db->query( $req );

        return $query->row()->count;
    }



    function get_min_time($user_id, $param = array()) {

        $req = 'SELECT  min(DATE(' . $this->records_table . '.start_time)) as mintime
             '.$this->param2fromwhere($user_id, $param);

        $query = $this->db->query( $req );

        return $query->row()->mintime;
    }


    function get_max_time($user_id, $param = array()) {


        /*$req = 'SELECT  ADDDATE(max(DATE(' . $this->records_table . '.start_time)), INTERVAL 1 DAY) as maxtime
             '.$this->param2fromwhere($user_id, $param);*/
        $req = 'SELECT  max(DATE(' . $this->records_table . '.start_time)) as maxtime
             '.$this->param2fromwhere($user_id, $param);

        $query = $this->db->query( $req );
        $res= $query->row()->maxtime;

        $param['running']=true;
        $nb_running=$this->get_records_count($user_id, $param);
        if ($nb_running>0) $res='running';

        return $res;
    }

    function trim_duration($record,$datemin,$datemax) { //unix time
    // A REVOIR SANS UNIX
        $date_deb =strtotime($record['start_time']);
        $date_fin = $date_deb + $record['duration'];

        if ($record['running']==1) $date_fin = time();

        if ($datemin) $date_deb = max( $date_deb , $datemin );
        if ($datemax) $date_fin = min( $date_fin , $datemax );

        $res= $date_fin-$date_deb;
        if ($res<0) $res=0;
        return $res;
    }


    function param2fromwhere($user_id, $param) {
        if (!isset( $param['categorie'] )) $param['categorie'] = NULL;
        if (!isset( $param['activity'] )) $param['activity'] = NULL;
        if (!isset( $param['type_of_record'] )) $param['type_of_record'] = NULL;
        if (!isset( $param['running'] )) $param['running'] = NULL;
        if (!isset( $param['tags'] )) $param['tags'] = array();
        if (!isset( $param['valuetype'] )) $param['valuetype'] = NULL;
        if (!isset( $param['order'] )) $param['order'] = 'DESC';

        $req='FROM ' . $this->records_table . '
             LEFT JOIN ' . $this->activities_table . '
                ON ' . $this->records_table . '.activity_ID=' . $this->activities_table . '.id
             LEFT JOIN ' . $this->categories_table . '
                ON ' . $this->activities_table . '.categorie_ID=' . $this->categories_table . '.id';


        if ($param['valuetype'] !== NULL )
            $req .=' LEFT JOIN ' . $this->records_values_table . '
                ON ' . $this->records_table . '.id=' . $this->records_values_table . '.record_ID';

         foreach ( $param['tags'] as $k => $tag )
            $req .=' LEFT JOIN ' . $this->records_tags_table . ' as tag_table_'.$k.'
                ON ' . $this->records_table . '.id= tag_table_'.$k.'.record_ID';


        $req .= ' WHERE
                user_ID=' . $user_id ;

        if ($param['categorie'] !== NULL ) $req .= ' AND ' . $this->activities_table . '.categorie_ID=' . $param['categorie'];
        if ($param['activity'] !== NULL ) $req .= ' AND ' . $this->records_table . '.activity_ID=' . $param['activity'];
        if ($param['type_of_record'] !== NULL ) $req .= ' AND type_of_record=\'' . $param['type_of_record'] . '\'';

        if ($param['running'] !== NULL )  $req .= ' AND running=' . $param['running'];

        if ($param['valuetype'] !== NULL ) $req .=  ' AND ' . $this->records_values_table . '.valuetype_ID='.$param['valuetype'];

        foreach ( $param['tags'] as $k => $tag )
            $req .=' AND tag_table_'.$k.'.tag_ID='.$tag;

        if ((isset( $param['datemin'] ))&&(isset( $param['datemax'] )))
            $req .=' AND
                        (UNIX_TIMESTAMP(start_time)+duration >= \''.  strtotime($param['datemin']. " UTC") .'\'
                        OR running=1)
                     AND start_time<\''.$param['datemax'].'\'';

        $req .= ' ORDER BY running DESC ,start_time '.$param['order'];

        return $req;
    }


    function get_records_full($user_id, $param = array(), $offset= NULL, $count= NULL ) {

        if (!isset( $param['categorie'] )) $param['categorie'] = NULL;
        if (!isset( $param['activity'] )) $param['activity'] = NULL;
        if (!isset( $param['type_of_record'] )) $param['type_of_record'] = NULL;
        if (!isset( $param['running'] )) $param['running'] = NULL;
        if (!isset( $param['tags'] )) $param['tags'] = array();
        if (!isset( $param['valuetype'] )) $param['valuetype'] = NULL;

        $activities = $this->get_records($user_id, $param, $offset, $count);
        if ( $activities )
            $activities = $this->complete_records_info( $activities );

        return $activities;
    }




    function get_record_by_id_full( $record_id ) {
        $activitie = $this->get_record_by_id( $record_id );
        if ( $activitie )
            $activitie = $this->complete_record_info( $activitie );

        return $activitie;
    }



    function restart_record( $record_id ) {
        $record = $this->get_record_by_id( $record_id );
        $param  = array(
             'description' => $record[ 'description' ]
        );

        if ( ( $record[ 'type_of_record' ] == 'value' ) || ( ( !$record[ 'running' ] ) && ( $record[ 'duration' ] == 0 ) ) )
            $param[ 'running' ] = 0;

        $new_record = $this->create_record( $record[ 'activity_ID' ], $param );

        foreach ( $record[ 'tags' ] as $k => $tag )
            $this->tags->add_tag( $new_record[ 'id' ], $tag[ 'id' ] ); // add tags

        if ( $record[ 'type_of_record' ] == 'value' )
            $this->values->add_value( $new_record[ 'id' ], $record[ 'value' ][ 'valuetype_ID' ], $record[ 'value' ][ 'value' ] );

        return TRUE;
    }


    function stop_record( $id ) {
        $record   = $this->get_record_by_id( $id );
        $duration = $this->calcul_duration( $record );
        print_r($duration);
        return $this->update_record( $id, array(
             'duration' => $duration,
            'running' => 0
        ) );
    }


    function calcul_duration( $record, $endtime = NULL ) {
        if ( $endtime == NULL )
            $endtime = time();
        $duration = $endtime - $record[ 'UNIXTIME' ];
        return $duration;
    }



    function complete_records_info( $records ) {
        foreach ( $records as $k => $record )
            $records[ $k ] = $this->complete_record_info( $record );

        return $records;
    }




    function complete_record_info( $record ) {



        if ( $record[ 'running' ] ){
            $record[ 'duration' ] = $this->calcul_duration( $record );
            $record[ 'stop_at' ] = NULL;
        }
        else
            $record[ 'stop_at' ] = date( "Y-m-d H:i:s", strtotime( $record[ 'start_time' ] ) + $record[ 'duration' ] );


        $record[ 'tags' ]  = $this->tags->get_record_tags( $record[ 'id' ] );
        $record[ 'value' ] = $this->values->get_record_value( $record[ 'id' ] );

        $record[ 'tag_path' ] = '';
        if ( $record[ 'tags' ] )
            foreach ( $record[ 'tags' ] as $k => $tag ) {
                if ( $record[ 'tag_path' ] != '' )
                    $record[ 'tag_path' ] .= ', ';
                $record[ 'tag_path' ] .= $tag[ 'tag' ];
            }

        if ( $record[ 'value' ] ) {
            $record[ 'value' ][ 'value_path' ] = '#'.$record[ 'value' ]['title'].'='.$record[ 'value' ]['value'];
            $record[ 'value_title' ] = $record[ 'value' ]['title'];
            $record[ 'value_data' ] = $record[ 'value' ]['value'];
            }


        $record[ 'activity' ] = $this->activities->get_activity_by_id_full( $record[ 'activity_ID' ] );


        return $record;
    }


} // END Class
