<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Tt_tags extends CI_Model
{
    private $table_name             = 'tags';
    private $table_link_name        = 'l_activities_tags';



    /**
     * get tag
     *
     * @user_id         int
     * @tag           string
     * @return          array
     */
    function get_tag( $user_id, $tag )
    {
        $this->db->where('tag', $tag);
        $this->db->where('user_ID', $user_id);

        $query = $this->db->get($this->table_name);
        if ($query->num_rows() == 1) return $query->row_array();
        return NULL;
    }


    /**
     * get tag list
     *
     * @user_id         int
     * @return          array
     */
    function get_tag_list( $user_id )
    {
       $query =  $this->db->query(
            'SELECT tags . * , count( activity_id ) AS count
            FROM tags
                LEFT JOIN l_activities_tags ON tags.id = l_activities_tags.tag_ID
            WHERE user_ID="'.$user_id.'"
            GROUP BY id
            ORDER BY tag');

        if ($query->num_rows() >= 1) return $query->result_array();
        return NULL;
    }


    /**
     * Create new tag record
     *
     * @user_id         int
     * @tag             string
     * @return          array
     */
    function create_tag( $user_id, $tag )
    {
        if ( $this->db->insert($this->table_name, array('tag'=>$tag, 'user_ID'=>$user_id)) ) {
               $data = $this->get_tag( $user_id, $tag );
            return $data;
        }
        return NULL;
    }


    /**
     * Update tag
     *
     * @user_id         int
     * @tag             string
     * @param           array
     * @return          boolean
     */
    function update_tag( $user_id, $tag, $param )
    {
        $this->db->where('user_ID', $user_id);
        $this->db->where('tag', $tag);
        if ($this->db->update($this->table_name, $param ))
            return TRUE;

        return FALSE;
    }

    /**
     * Get or Create new tag record
     *
     * @user_id         int
     * @tag           string
     * @return          array
     */
    function getorcreate_tag( $user_id, $tag )
    {
        $res=$this->get_tag($user_id, $tag);
        if (!$res) $res=$this->create_tag($user_id, $tag);

        return $res;
    }


    /**
     * add tag
     *
     * @activity_id     int
     * @tag_id          int
     * @return          boolean
     */
    function add_tag( $activity_id, $tag_id )
    {
        return $this->db->insert($this->table_link_name, array('activity_ID'=>$activity_id, 'tag_ID'=>$tag_id));

    }

    /**
     * remove tag
     *
     * @activity_id     int
     * @tag_id          int
     * @return          boolean
     */
    function remove_tag( $activity_id, $tag_id )
    {
        $res= $this->db->delete($this->table_link_name, array('activity_ID'=>$activity_id, 'tag_ID'=>$tag_id));
        $this->clear_orphan();
        return $res;

    }


     function clear_orphan()
     {
         // TODO !
         // clear table_link where not in tags or not in activities
         // clear tags where not in table_link SHOULD WE ???
     }


} // END Class
