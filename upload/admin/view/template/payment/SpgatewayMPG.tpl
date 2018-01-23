<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
    <div class="page-header">
        <div class="container-fluid">
            <div class="pull-right">
                <button type="submit" form="form-amazon-checkout" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
                <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a></div>
            <h1><?php echo $heading_title; ?></h1>
            <ul class="breadcrumb">
                <?php foreach ($breadcrumbs as $breadcrumb) { ?>
                <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
                <?php } ?>
            </ul>
        </div>
    </div>
    <div class="container-fluid">
        <?php if ($error_warning) { ?>
        <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php } ?>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $heading_title; ?></h3>
            </div>
            <div class="panel-body">

                <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-amazon-checkout" class="form-horizontal">

                    <div class="form-group required">
                        <label class="col-sm-2 control-label"><?php echo $heading_title; ?></label>
                        <div class="col-sm-10">
                            <input type="text" name="<?php echo $entry_subfix; ?>_description" value="智付通 Spgateway 第三方支付金流 ( Spgateway )" class="form-control" />
                        </div>
                    </div>	

                    <div class="form-group">
                        <label class="col-sm-2 control-label"><?php echo $entry_test_mode; ?></label>
                        <div class="col-sm-10">
                            <select name="<?php echo $entry_subfix; ?>_test_mode" class="form-control">
                                <option value="1" <?php echo ($test_mode ? 'selected="selected"' : ''); ?> >Yes</option>
                                <option value="0" <?php echo (!$test_mode ? 'selected="selected"' : ''); ?> >No</option>
                            </select>
                        </div>
                    </div>		  

                    <div class="form-group required">
                        <label class="col-sm-2 control-label"><?php echo $entry_merchant_id; ?></label>
                        <div class="col-sm-10">
                            <input type="text" name="<?php echo $entry_subfix; ?>_merchant_id" value="<?php echo isset($merchant_id) ? $merchant_id : ''; ?>" class="form-control" />
                            <?php if ($error_warning2) { ?>
                            <div class="text-danger"><?php echo $error_warning2; ?></div>
                            <?php } ?>			  
                        </div>
                    </div>		  

                    <div class="form-group required">
                        <label class="col-sm-2 control-label"><?php echo $entry_hash_key; ?></label>
                        <div class="col-sm-10">
                            <input type="text" name="<?php echo $entry_subfix; ?>_hash_key" value="<?php echo isset($hash_key) ? $hash_key : ''; ?>" class="form-control" />
                            <?php if ($error_warning3) { ?>
                            <div class="text-danger"><?php echo $error_warning3; ?></div>
                            <?php } ?>				  
                        </div>
                    </div>		  

                    <div class="form-group required">
                        <label class="col-sm-2 control-label"><?php echo $entry_hash_iv; ?></label>
                        <div class="col-sm-10">
                            <input type="text" name="<?php echo $entry_subfix; ?>_hash_iv" value="<?php echo isset($hash_iv) ? $hash_iv : ''; ?>" class="form-control" />
                            <?php if ($error_warning4) { ?>
                            <div class="text-danger"><?php echo $error_warning4; ?></div>
                            <?php } ?>				  
                        </div>
                    </div>	

                    <div class="form-group required">
                        <label class="col-sm-2 control-label"><span data-toggle="tooltip" title="<?php echo $entry_item_desc_content; ?>"><?php echo $entry_item_desc; ?></span></label>
                        <div class="col-sm-10">
                            <input type="text" name="<?php echo $entry_subfix; ?>_item_desc" value="<?php echo isset($item_desc) ? $item_desc : ''; ?>" class="form-control" />
                            <?php if ($error_warning5) { ?>
                            <div class="text-danger"><?php echo $error_warning5; ?></div>
                            <?php } ?>				  
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label"><?php echo $entry_order_status; ?></label>
                        <div class="col-sm-10">
                            <select name="<?php echo $entry_subfix; ?>_order_status_id" class="form-control">
                                <?php foreach ($order_statuses as $order_status) { ?>
                                <?php if ($order_status['order_status_id'] == $order_status_id) { ?>
                                <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
                                <?php } else { ?>
                                <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                                <?php } ?>
                                <?php } ?>
                            </select>			  
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label"><?php echo $entry_order_finish_status; ?></label>
                        <div class="col-sm-10">
                            <select name="<?php echo $entry_subfix; ?>_order_finish_status_id" class="form-control">
                                <?php foreach ($order_statuses as $order_status) { ?>
                                <?php if ($order_status['order_status_id'] == $order_finish_status_id) { ?>
                                <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
                                <?php } else { ?>
                                <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                                <?php } ?>
                                <?php } ?>
                            </select>			  
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label"><?php echo $entry_order_fail_status; ?></label>
                        <div class="col-sm-10">
                            <select name="<?php echo $entry_subfix; ?>_order_fail_status_id" class="form-control">
                                <?php foreach ($order_statuses as $order_status) { ?>
                                <?php if ($order_status['order_status_id'] == $order_fail_status_id) { ?>
                                <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
                                <?php } else { ?>
                                <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                                <?php } ?>
                                <?php } ?>
                            </select>			  
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label"><?php echo $entry_geo_zone; ?></label>
                        <div class="col-sm-10">
                            <select name="<?php echo $entry_subfix; ?>_geo_zone_id" class="form-control">
                                <option value="0"><?php echo $text_all_zones; ?></option>
                                <?php foreach ($geo_zones as $geo_zone) { ?>
                                <?php if ($geo_zone['geo_zone_id'] == $geo_zone_id) { ?>
                                <option value="<?php echo $geo_zone['geo_zone_id']; ?>" selected="selected"><?php echo $geo_zone['name']; ?></option>
                                <?php } else { ?>
                                <option value="<?php echo $geo_zone['geo_zone_id']; ?>"><?php echo $geo_zone['name']; ?></option>
                                <?php } ?>
                                <?php } ?>
                            </select>			  
                        </div>
                    </div>		  

                    <div class="form-group">
                        <label class="col-sm-2 control-label"><?php echo $entry_status; ?></label>
                        <div class="col-sm-10">
                            <select name="<?php echo $entry_subfix; ?>_status" class="form-control">
                                <?php if ($status) { ?>
                                <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                                <option value="0"><?php echo $text_disabled; ?></option>
                                <?php } else { ?>
                                <option value="1"><?php echo $text_enabled; ?></option>
                                <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                                <?php } ?>
                            </select>			  
                        </div>
                    </div>		

                    <div class="form-group">
                        <label class="col-sm-2 control-label"><?php echo $entry_sort_order; ?></label>
                        <div class="col-sm-10">
                            <input type="text" name="<?php echo $entry_subfix; ?>_sort_order" value="<?php echo $sort_order; ?>" class="form-control" />
                        </div>
                    </div>		  

                </form>
            </div>
        </div>
    </div>
</div>
<?php echo $footer; ?>