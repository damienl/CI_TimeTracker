<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Tt_activities extends CI_Model
{
    private $activities_table   = 'activities';
    private $categories_table   = 'categories';


    /**
     * Get activity by Id
     *
     * @activity_id     int
     * @return          array
     */
    function get_activity_by_id($activity_id)
    {
        $this->db->where('id', $activity_id);

        $query = $this->db->get($this->activities_table);
        if ($query->num_rows() == 1) return $query->row_array();
        return NULL;
    }


    /**
     * Get activity
     *
     * @categorie_id    int
     * @title           string
     * @type_record     string
     * @return          array
     */
    function get_activity( $categorie_id,$title,$type_record )
    {
        $this->db->where('categorie_ID', $categorie_id);
        $this->db->where('title', $title);
        $this->db->where('type_of_record', $type_record);

        $query = $this->db->get($this->activities_table);
        if ($query->num_rows() == 1) return $query->row_array();
        return NULL;
    }


    /**
     * Create new activity record
     *
     * @categorie_id    int
     * @title           string
     * @type_record     string
     * @return          array
     */
    function create_activity( $categorie_id,$title,$type_record )
    {
        $param =array('title' => strtolower($title), 'categorie_id' => $categorie_id, 'type_of_record' =>$type_record );


        if ($this->db->insert($this->activities_table, $param)) {
               $data = $this->get_activity_by_id( $this->db->insert_id() );
            return $data;
        }
        return NULL;
    }



    /**
     * get or create activity record
     *
     * @categorie_id    int
     * @title           string
     * @param           array
     * @return          array
     */
    function getorcreate_activity($categorie_id,$title,$type_record )
    {
        $res=$this->get_activity($categorie_id,$title,$type_record );
        if (!$res) $res=$this->create_activity( $categorie_id,$title,$type_record );

        return $res;
    }


    /**
     * Update activity
     *
     * @activity_id     int
     * @title           string
     * @return          boolean
     */
    function update_activity( $activity_id, $param )
    {
        $this->db->where('id', $activity_id);

        if ($this->db->update($this->activities_table, $param)) return TRUE;

        return FALSE;
    }

    /**
     * Get running activities
     *
     * @user_id     int
     * @return          array
     */
    function get_running_activities($user_id)
    {
        $query =  $this->db->query(
            'SELECT activities.*
             FROM activities
                LEFT JOIN categories
                ON activities.categorie_ID=categories.id
            WHERE user_ID='.$user_id.'
            AND running=1
            ORDER BY start_UNIX DESC'
            );

        if ($query->num_rows() >= 1) return $query->result_array();
        return NULL;
    }

    /**
     * Get last activities
     *
     * @user_id     int
     * @return          array
     */
    function get_last_activities($user_id,$offset,$count)
    {
        $query =  $this->db->query(
            'SELECT activities.*
             FROM activities
                LEFT JOIN categories
                ON activities.categorie_ID=categories.id
            WHERE user_ID='.$user_id.'
            AND running=0
            ORDER BY UNIX_TIMESTAMP(start_UNIX)+duration DESC
            LIMIT '.$offset.','.$count
            );

        if ($query->num_rows() >= 1) return $query->result_array();
        return NULL;
    }



    function stop_activity($id, $endtime)
    {
        $activity= $this->get_activity_by_id($id);
        $duree= $endtime - strtotime( $activity['start_UNIX'] );

    }

} // END Class
