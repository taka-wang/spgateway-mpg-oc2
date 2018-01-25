<?php 
/**
 * @author HughesWu extend Roger
 */
class ModelPaymentSpgatewayMPG extends Model {
    
    public function getMethod($address, $total) {
        		
        $this->load->language('payment/SpgatewayMPG');
		
        $sql  = "SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE";
        $sql .= " geo_zone_id = '" . (int)$this->config->get('SpgatewayMPG_geo_zone_id') . "'";
        $sql .= " AND country_id = '" . (int)$address['country_id'] . "'";
        $sql .= " AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')";
                
        $query = $this->db->query($sql);

        $status = true;
		
		if (!$this->config->get('SpgatewayMPG_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}		
				
        $method_data = array();
	
        if ($status) {  
            $method_data = array( 
                'code'       => 'SpgatewayMPG',
                'title'      => $this->language->get('text_title'),
				'terms'		 => '',
                'sort_order' => $this->config->get('SpgatewayMPG_sort_order')
            );
			
			$method_data["title"] .= "  (" . $this->config->get('SpgatewayMPG_description') . ")";
    	}
		
    	return $method_data;
    }

}
?>