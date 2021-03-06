<?php

###############################################################################
# ASTPP - Open Source VoIP Billing Solution
#
# Copyright (C) 2016 iNextrix Technologies Pvt. Ltd.
# Samir Doshi <samir.doshi@inextrix.com>
# ASTPP Version 3.0 and above
# License https://www.gnu.org/licenses/agpl-3.0.html
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as
# published by the Free Software Foundation, either version 3 of the
# License, or (at your option) any later version.
# 
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
# 
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
###############################################################################

class Refill_coupon extends MX_Controller {
    
	function Refill_coupon(){
		  parent::__construct();
		  $this->load->helper('template_inheritance');
		  $this->load->helper('form');
		  $this->load->model('refill_coupon_model');
		  $this->load->library("refill_coupon_form");
		  $this->load->library("astpp/form");
		  $this->load->library("session");
		  if ($this->session->userdata('user_login') == FALSE)
			redirect(base_url() . 'login/login');
	  }
	function refill_coupon_list(){
	if($this->session->userdata('logintype') == 0 || $this->session->userdata('logintype') == 3){
			redirect(base_url() . 'user/user/');
	}
		$data['username'] = $this->session->userdata('user_name');
		$data['page_title'] = gettext('Refill Coupon');
		$data['search_flag'] = true;
		$this->session->set_userdata('advance_search', 0);
		$this->session->set_userdata('refill_coupon_list_search', 0);
		$data['grid_fields'] = $this->refill_coupon_form->build_refill_coupon_grid();
		$data["grid_buttons"] = $this->refill_coupon_form->build_grid_buttons_refill_coupon();
		$data['form_search'] = $this->form->build_serach_form($this->refill_coupon_form->get_refill_coupon_search_form());
		$this->load->view('view_refill_coupon_list', $data);
	}
	function refill_coupon_list_json(){
	$json_data = array();
		$count_all = $this->refill_coupon_model->get_refill_coupon_list(false, "", "");
		$paging_data = $this->form->load_grid_config($count_all, $_GET['rp'], $_GET['page']);
		$json_data = $paging_data["json_paging"];
		$query = $this->refill_coupon_model->get_refill_coupon_list(true, $paging_data["paging"]["start"], $paging_data["paging"]["page_no"]);
		$grid_fields = json_decode($this->refill_coupon_form->build_refill_coupon_grid());
		$json_data['rows'] = $this->form->build_grid($query, $grid_fields);
		echo json_encode($json_data);
	}   
	function refill_coupon_list_search(){
		$ajax_search = $this->input->post('ajax_search', 0);
		if ($this->input->post('advance_search', TRUE) == 1) {
			$this->session->set_userdata('advance_search', $this->input->post('advance_search'));
			$action = $this->input->post();
			unset($action['action']);
			unset($action['advance_search']);
			if (isset($action['amount']['amount']) && $action['amount']['amount'] != '') {
				$action['amount']['amount'] = $this->common_model->add_calculate_currency($action['amount']['amount'], "", '', true, false);
			}
			$this->session->set_userdata('refill_coupon_list_search', $action);
		}
		if (@$ajax_search != 1) {
			redirect(base_url() . 'refill_coupon/refill_coupon_list/');
		}
	}
	function refill_coupon_clearsearchfilter(){
	$this->session->set_userdata('advance_search', 0);
		$this->session->set_userdata('refill_coupon_list_search', "");
	}
	function refill_coupon_list_view($id){
	$data['username'] = $this->session->userdata('user_name');
		$data['page_title'] = 'View Refill Coupon';
		if ($cc = $this->refill_coupon_model->get_refill_coupon_details($id)) {
		$refill_coupon_details=$cc->result_array();
		$data['refill_coupon_details']=$refill_coupon_details[0];
			$data['refill_coupon_details']['callingcard']= $this->common->get_field_name('cardnumber', 'callingcards', $refill_coupon_details[0]['callingcard_id']);
			$data['refill_coupon_details']['currency']= $this->common->build_concat_string('currencyname,currency', 'currency', $refill_coupon_details[0]['currency_id']);
		} else {
			echo "This card is not available.";
			return;
		}
		$this->load->view('view_refill_coupon_details', $data);
    
	}
	function refill_coupon_add() {
	if($this->session->userdata('logintype') == 0 || $this->session->userdata('logintype') == 3){
			redirect(base_url() . 'user/user/');
	}
		$data['username'] = $this->session->userdata('user_name');
		$data['flag'] = 'create';
		$data['page_title'] = 'Create Refill Coupon';
		$data['form'] = $this->form->build_form($this->refill_coupon_form->get_refill_coupon_form_fields(), '');
		$this->load->view('view_refill_coupon_add', $data);
	}
	function refill_coupon_save() {
		$account_length=Common_model::$global_config['system_config']['refill_coupon_length'];
		$add_array = $this->input->post();
		$add_array['status']=0;
		$data['form'] = $this->form->build_form($this->refill_coupon_form->get_refill_coupon_form_fields(), $add_array);
	$result=$this->refill_coupon_model->refill_coupon_count($add_array);
		$count=$result[0]['count'];
		$remaining_length =0;
		$remaining_length=$account_length - strlen($add_array['prefix']);
		$currentlength =pow(5,$remaining_length);
		$currentlength=$currentlength-$count;
		$data['page_title'] = 'Add Refill Coupon';
		if ($this->form_validation->run() == FALSE) {
				$data['validation_errors'] = validation_errors();
				echo $data['validation_errors'];
				exit;
		}
		else {    
		  if($account_length<=strlen($add_array['prefix'])){
			  echo json_encode(array("count_error"=>"You Can Not Create ".$add_array['count']." Accounts with ".$add_array['prefix']." prefix."));
			  exit;      
		  }
		  if($currentlength <= 0){
			echo json_encode(array("count_error"=>"You Can Not Create ".$add_array['count']." Accounts with ".$add_array['prefix']." prefix"));
			exit;  
		  }
		  if($currentlength > 0 && $add_array['count'] > $currentlength){
			echo json_encode(array("count_error"=>"You Can Create Maximum ".$currentlength." accounts with ".$add_array['prefix']." prefix"));
			exit;      
		  }else{
			$this->refill_coupon_model->add_refill_coupon($add_array);
			echo json_encode(array("SUCCESS"=> "Refill coupon created successfully!"));
			exit;
		  }
		}
	 }       
	function refill_coupon_list_delete($id) {
		$this->refill_coupon_model->remove_refill_coupon($id);
		$this->session->set_flashdata('astpp_notification', 'Refill coupon removed successfully!');
		redirect(base_url() . 'refill_coupon/refill_coupon_list/');
	}
	function refill_coupon_export(){
	 $account_info = $accountinfo = $this->session->userdata('accountinfo');
		$currency_id=$account_info['currency_id'];
		$currency=$this->common->get_field_name('currency', 'currency', $currency_id);
	$query = $this->refill_coupon_model->get_refill_coupon_list(true,'','',true);
		$cc_array = array();
		ob_clean();
		$cc_array[] = array('Coupon No.','Description', 'Account','Amount('.$currency.')','Created Date',"Used?",'Used Date');
		if ($query->num_rows() > 0) {
			foreach ($query->result_array() as $row) {
				$row['currency'] = $this->common->build_concat_string('currencyname,currency', 'currency', $row['currency_id']);
				$row['acc_name'] = $row['account_id'] > 0 ? $this->common->get_field_name('number', 'accounts', $row['account_id']) : "";
				$row['status']=$this->common->get_refill_coupon_status('','',$row['status']);
				$cc_array[] = array(
					$row['number'],
					$row['description'],
					$row['acc_name'],
					$this->common->convert_to_currency('','',$row['amount']),
					$row['creation_date'],
					$row['status'],
					$row['firstused']
				);
			}
		}
		$this->load->helper('csv');
		array_to_csv($cc_array, 'refill_coupon_' . date("Y-m-d") . '.csv');
	}
	function refill_coupon_customer_json($accountid){
	$json_data = array();
		$count_all = $this->refill_coupon_model->get_customer_refill_coupon_list(false, "", "",$accountid);
		$paging_data = $this->form->load_grid_config($count_all, $_GET['rp'], $_GET['page']);
		$json_data = $paging_data["json_paging"];
		$query = $this->refill_coupon_model->get_customer_refill_coupon_list(true, $paging_data["paging"]["start"], $paging_data["paging"]["page_no"],$accountid);
		$grid_fields = json_decode($this->refill_coupon_form->build_user_refill_coupon_grid());
		$json_data['rows'] = $this->form->build_grid($query, $grid_fields);
		echo json_encode($json_data);
	}
} 
?>
