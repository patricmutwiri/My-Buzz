<?php
class ControllerExtensionModuleMybuzz extends Controller {
    private $error = array(); 
    protected function articleTables()
    {
        // create table
        $articlesT = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "articles'");
        if(!$articlesT->num_rows) {
            $query = "CREATE TABLE ".DB_PREFIX."articles (
                      id int(11) AUTO_INCREMENT,
                      title varchar(50) NOT NULL,
                      content varchar(200) NOT NULL,
                      county varchar(10) NOT NULL,
                      created_at varchar(20) NOT NULL,
                      updated_at varchar(20),
                      status int,
                      PRIMARY KEY  (id)
                      )";
            if(!$this->db->query($query)) {
                error_log('articles table creation failed');
                $this->error['code'] = 'articles table creation failed';
            }
        }
        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }

    public function index() {
        $this->load->language('extension/module/mybuzz'); 
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/module');
        if (isset($this->request->get['remove_id'])) { 
            $this->deleteArticle();
        }
        if (isset($this->request->get['module_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $module_info = $this->model_setting_module->getModule($this->request->get['module_id']);
        }
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $module_info = $this->model_setting_module->getModule($this->request->get['module_id']);
            $settings = array();
            $settings['status']    = $this->request->post['status'];
            $settings['limit']     = $this->request->post['limit'];
            $settings['name']      = $this->request->post['name'];
            if (!isset($this->request->get['module_id'])) {
                $this->model_setting_module->addModule('mybuzz', $settings);
            } else {
                $this->model_setting_module->editModule($this->request->get['module_id'], $settings);
            }
            $status = $this->saveArticles();
            $data['jstatus'] = json_encode($status);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
        }
     
        $data['heading_title'] = $this->language->get('heading_title');
     
        $data['text_edit']    = $this->language->get('text_edit');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['text_content_top'] = $this->language->get('text_content_top');
        $data['text_content_bottom'] = $this->language->get('text_content_bottom');      
        $data['text_column_left'] = $this->language->get('text_column_left');
        $data['text_column_right'] = $this->language->get('text_column_right');
     
        $data['entry_code'] = $this->language->get('entry_code');
        $data['entry_limit'] = $this->language->get('entry_limit');
        $data['entry_layout'] = $this->language->get('entry_layout');
        $data['entry_title'] = $this->language->get('entry_title');
        $data['entry_content'] = $this->language->get('entry_content');
        $data['entry_county'] = $this->language->get('entry_county');
        $data['entry_created_at'] = $this->language->get('entry_created_at');
        $data['entry_updated_at'] = $this->language->get('entry_updated_at');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_sort_order'] = $this->language->get('entry_sort_order');
     
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');
        $data['button_add_module'] = $this->language->get('button_add_module');
        $data['button_remove'] = $this->language->get('button_remove');
         
        // This Block returns the warning if any
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
     
        // This Block returns the error code if any
        if (isset($this->error['code'])) {
            $data['error_code'] = $this->error['code'];
        } else {
            $data['error_code'] = '';
        }     
     
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );

        if (!isset($this->request->get['module_id'])) {
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('extension/module/mybuzz', 'user_token=' . $this->session->data['user_token'], true)
            );
        } else {
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('extension/module/mybuzz', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true)
            );
        }
        
        $data['del_url'] = $this->url->link('extension/module/mybuzz', 'user_token=' . $this->session->data['user_token'], 'SSL');
        
        if (!isset($this->request->get['module_id'])) {
            $data['action'] = $this->url->link('extension/module/mybuzz', 'user_token=' . $this->session->data['user_token'], true);
        } else {
            $data['action'] = $this->url->link('extension/module/mybuzz', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true);
        }
     
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
        
        if (isset($this->request->post['name'])) {
            $data['name'] = $this->request->post['name'];
        } elseif (!empty($module_info)) {
            $data['name'] = $module_info['name'];
        } else {
            $data['name'] = '';
        }

        if (isset($this->request->post['limit'])) {
            $data['limit'] = $this->request->post['limit'];
        } elseif (!empty($module_info)) {
            $data['limit'] = $module_info['limit'];
        } else {
            $data['limit'] = '';
        }

        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } elseif (!empty($module_info)) {
            $data['status'] = $module_info['status'];
        } else {
            $data['status'] = '';
        }

        // list articles
        $data['totalarticles'] = 0;
        $data['articles'] = null;
        if($this->articleTables()) {
            $articles = $this->db->query('SELECT * FROM '.DB_PREFIX.'articles');
            $data['articles'] = $articles->rows;
            $data['totalarticles'] = count($data['articles']);
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $counties = $this->db->query("SELECT * FROM `wwvc_zone` WHERE `country_id` = 110"); //kenya
        if($counties->num_rows) {
            $data['counties'] = $counties;
        } else {
            $data['counties'] = 0;
        }
        $this->response->setOutput($this->load->view('extension/module/mybuzz', $data));

    }
    // delete job
    protected function deleteArticle()
    {
        if (isset($this->request->get['remove_id'])) {
            $id = $this->request->get['remove_id'];
            $delArticle = $this->db->query("DELETE FROM ".DB_PREFIX."articles WHERE id = '".(int)$id."'");
            if($delArticle){
                $this->session->data['success'] = 'Article removed successfully';
            } else {
                $this->session->data['success'] = 'Article not removed';
            }
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
        }
    }

    // save to db
    protected function saveArticles()
    {
        if (isset($this->request->post['mybuzz_title_field'])) {
            $articles   = array();
            $articles['status'] = '';
            $count  = count($_POST['mybuzz_title_field']);
            for ($i=0; $i < $count; $i++):
                $articles['mybuzz_title_field'][$i]         = $_POST['mybuzz_title_field'][$i];
                $articles['mybuzz_content_field'][$i]       = $_POST['mybuzz_content_field'][$i];
                $articles['mybuzz_county_field'][$i]        = $_POST['mybuzz_county_field'][$i];
                $articles['mybuzz_enabled_field'][$i]       = $_POST['mybuzz_enabled_field'][$i];
                $articles['mybuzz_created_at_field'][$i]    = date('d-m-y H:i', strtotime($_POST['mybuzz_created_at_field'][$i]));
                // If title exists
                $thisId = $this->db->query("SELECT * FROM " . DB_PREFIX . "articles WHERE id = '" . $this->db->escape($articles['mybuzz_id_field'][$i])."'");
                if($thisId->rows) {
                    // update
                    $articles['status'][] = "Title ". $articles['mybuzz_title_field'][$i]." exists, try update";
                    if($this->db->query("UPDATE " . DB_PREFIX . "articles SET `created_at` = '" . $this->db->escape($articles['mybuzz_created_at_field'][$i]) . "', `status` = '" . $this->db->escape($articles['mybuzz_enabled_field'][$i]) . "', `content` = '" . $this->db->escape($articles['mybuzz_content_field'][$i]) . "', `title` = '" . $this->db->escape($articles['mybuzz_title_field'][$i]) . "', `updated_at` = '" . $this->db->escape(date('d-m-y H:i', time())) . "' WHERE id = '" . $this->db->escape($articles['mybuzz_id_field'][$i]) . "'")) {
                        $articles['status'][] = "Article with Title ". $articles['mybuzz_title_field'][$i]." updated successfully";
                    } else {
                        $articles['status'][] = "Article with Title ". $articles['mybuzz_title_field'][$i]." not updated";
                    }
                } else {
                    if(!$this->db->query("INSERT INTO " . DB_PREFIX . "articles SET `created_at` = '" . $this->db->escape($articles['mybuzz_created_at_field'][$i]) . "', `title` = '" . $this->db->escape($articles['mybuzz_title_field'][$i]) . "', `status` = '" . $this->db->escape($articles['mybuzz_enabled_field'][$i]) . "', `content` = '" . $this->db->escape($articles['mybuzz_content_field'][$i]) . "', `county` = '" . $this->db->escape($articles['mybuzz_county_field'][$i]) . "', `created_at` = '" . $this->db->escape($articles['mybuzz_created_at_field'][$i]) . "'")) {
                        error_log($articles['mybuzz_title_field'][$i]. ' not saved ');
                        $articles['status'][] = $articles['mybuzz_title_field'][$i]. ' not saved';
                    } else {
                        error_log($articles['mybuzz_title_field'][$i]. ' well saved ');
                        $articles['status'][] = $articles['mybuzz_title_field'][$i]. ' saved';
                    }
                }
            endfor;
            return $articles['status'];
        } else {
            return false;
        }
        //save now
    }

    protected function validate() { 
        if (!$this->user->hasPermission('modify', 'extension/module/mybuzz')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
 
        if (!$this->request->post['mybuzz_title_field']) {
            $this->error['code'] = $this->language->get('error_code');
        }
        // create table
        $articlesT = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "articles'");
        if(!$articlesT->num_rows) {
            $query = "CREATE TABLE ".DB_PREFIX."articles (
                  id int(11) AUTO_INCREMENT,
                  title varchar(50) NOT NULL,
                  content varchar(200) NOT NULL,
                  county varchar(10) NOT NULL,
                  created_at varchar(20) NOT NULL,
                  status int,
                  PRIMARY KEY  (id)
                  )";
            if(!$this->db->query($query)) {
                error_log('articles table creation failed');
                $this->error['code'] = 'articles table creation failed';
            }
        }
        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }
}
