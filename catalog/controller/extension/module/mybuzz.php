<?php
class ControllerExtensionModuleMybuzz extends Controller {
    public function index($setting) {
        $this->load->language('extension/module/mybuzz');
        $limit = $setting['limit'];
        $status = $setting['status'];
        if(!$status) {
            echo 'Buzz not available';
        } else {
            $data['articles'] = array();
            $articles = null;
            $data['totalarticles'] = 0;
            // query 
            $query = $this->db->query("SELECT * FROM ".DB_PREFIX."articles WHERE status=1 ORDER BY created_at DESC");
            if($query->num_rows) {
                $articles = $query->rows;
            }
            $data['totalarticles'] = $query->num_rows;
            // filter
            $data['articles'] = $articles;
            //json
            if(isset($this->request->get['json'])) {
                if($this->request->get['json'] == 'patricks') { 
                    echo json_encode($data);
                }
            } else {
                return $this->load->view('extension/module/mybuzz', $data);
            }
        }
    }
}
