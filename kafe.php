<?php
//
// Copyright (c) 2010-2011, Virtual Apps LLC (http://www.virtual-apps.com)
// All rights reserved.
//
// Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
//
// - Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
// - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
//
// THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
//

function alr_() {/*{{{*/

}/*}}}*/
function bytes2human($bytes) {/*{{{*/
	if ($bytes >= 1000) {
		$kbytes = $bytes/1024;
		if ($kbytes >= 1000) {
			$mbytes = $kbytes/1024;
			if ($mbytes >= 1000) {
				$gbytes = $mbytes/1024;
				if ($gbytes >= 1000) {
					$tbytes = $gbytes/1024;
					return ceil($tbytes).' TB';
				} else {
					return ceil($gbytes).' GB';
				}
			} else {
				return ceil($mbytes).' MB';
			}
		} else {
			return ceil($kbytes).' KB';
		}
	} else {
		return $bytes;
	}
}/*}}}*/
function cki_() {/*{{{*/

}/*}}}*/
function cst_arr($a, $array_key='name') {/*{{{*/
    // cst_arr  cast_array - cast the argument into array, if scalar, then $a is 'name'
    // $a           value
    // $array_key   default key to assign
    if (!is_array($a)) {
        $tmp_a = $a;
        $a = array();
        $a["$array_key"] = $tmp_a;
    }
    return $a;
}/*}}}*/
function cst_def($var, $default='', $index='') {/*{{{*/
    // $var       =   $var['image']
    // $index	  =	  image
    // $default   = 'generic.png'
    if ($index) {
    	if(isset($var[$index]) && $var) {
    		$default = $var[$index];
    	}
    } else {
	    if (isset($var) && $var) {
	        $default = $var;
	    }
    }
    return $default;
}/*}}}*/
function cst_ses($a) {/*{{{*/
	// cast default post or session
	// $default empty might be '' or array()
	$form_submit = $a['form_submit'];
	$field_names = $a['field_names'];
	if (isset($_POST[$form_submit]) && $_POST[$form_submit]) {
		foreach ($field_names as $field_name => $default_value) {
			//$_SESSION[$field_name] = cst_def($_POST[$field_name], $default_value);
			$_SESSION[$field_name] = cst_def($_POST, $default_value, $field_name);
		}
	} else {
		foreach ($field_names as $field_name => $default_value) {
			if (!isset($_SESSION[$field_name])) $_SESSION[$field_name] = $default_value;
		}
	}
}/*}}}*/
function cst_lbl($a=array()) {/*{{{*/
    // cst_lbl  cast label
    $name = $a['name'];
    $label = $a['label'];
    if (!$label) {
        $label = $name;
        $label = preg_replace('/_/', ' ', $label);
        $label = ucwords($label);
    }
    return $label;
}/*}}}*/
function cvr_() {/*{{{*/
    // cover
}/*}}}*/
function dat_c2c($col_name, $col_value, $col_fetch, $table) {/*{{{*/
	// data simple, very simple compared to the complex data functions like dat_pgn or dat_dst
	// c2c - col/field to col/field
	// provide a field/column and in return expect/get back a field/column
	// single col. to single col. - simple, sweet
	$query = "select `$col_fetch` from `$table` where `$col_name`='$col_value'";
	$result = mysql_query($query);
	$row = mysql_fetch_assoc($result);
	return $row[$col_fetch];
}/*}}}*/
function dat_upd_pid($a) {/*{{{*/
    // get data for update based on id
    $a = cst_arr($a, 'sql');
    $sql = $a['sql'];
    //$multiple = cst_def($a['multiple'], false);
    $multiple = cst_def($a, false, 'multiple');
    $data=array();/*{{{*/
    if (POST_SELF) {
        $data = $_POST['data'];
    } elseif($_POST['id']) {
        if ($multiple) {
            foreach ($_POST['id'] as $id) {
                eval('$sql = "'.$sql.'";');
                $r = dba_sel("$sql");
                $rows = $r['data'];
                $row  = $r['data'][0];
                $data[$row['id']] = $row;
            }
        } else {
            $id = array_pop($_POST['id']);
            eval('$sql = "'.$sql.'";');
            $r = dba_sel("$sql");
            $row = $r['data'][0];
            $data[$row['id']] = $row;
        }
    }/*}}}*/
    return $data;
}/*}}}*/
function dat_dst($a) {/*{{{*/
    // data distillation
    // input    $data
    // search   text field      [     ] variable
    // filters  select box      [red,green,blue] fixed
    //          format          $filter['col_name'] = array('col_values');
    // sort_col
    // strict [not implemented yet]

    $a = cst_arr($a, 'data');
    $data = $a['data'];
    $filters = cst_def($a, '', 'filters');
    $search  = cst_def($a, '', 'search');
    // search_ignores_filters sif
    $sif_flag = cst_def($a, 'no', 'sif_flag');
    $pagina  = cst_def($a, '', 'pagina');

    //$sort_col_def = cst_def($a['sort_col_def'], 'id:desc');
    $sort_col_def = cst_def($a, 'id:desc', 'sort_col_def');
    //$sort_order_def = cst_def($a['sort_order_def'], 'asc');

    //$table_h = cst_def($a['th'], array());
    $table_h = cst_def($a, array(), 'th');
    $ses_srt_name = $a['ses_srt_name'];
    $formname = $a['formname'];

    // and | or
    $filter_relation_atom = 'and';
    // and | or
    $filter_relation_element = 'or';

    // ddata    distilled data
    $ddata = array();
    foreach($data as $d) {
        $skip_data = false;
        // filter match atom
        $fm_a = array();
        foreach ($filters as $col_name => $col_values) {
            // filter match element
            $fm_e = array();
            foreach ($col_values as $col_value) {

                // int id
                if (preg_match("/\d+/", $col_value)) {
                    // we want to match (/1/, '1'), not (/1/, '19')
                    $col_value = preg_quote($col_value, '/');
                    $match_str = "/^$col_value$/i";
                } else {
                	$col_value = preg_quote($col_value, '/');
                    $match_str = "/$col_value/i";
                }

                // ($d["$col_name"]) might be an array eg $cat_ids
				if (is_array($d[$col_name])) {
					$dcn_match = false;
					foreach ($d[$col_name] as $dcn) {

						if (preg_match($match_str, $dcn)) {
							$dcn_match = true;
							break;
		                }
					}
					if ($dcn_match==true) {
					    $fm_e[] = 'yes';
					} else {
                   		$fm_e[] = 'no';
					}
				} else {
	                if (preg_match($match_str, $d["$col_name"])) {
	                    $fm_e[] = 'yes';
	                } else {
	                    $fm_e[] = 'no';
	                }
				}

            }
            // or
            if (in_array('yes', $fm_e)) {
                $fm_a[] = 'yes';
            } else {
                $fm_a[] = 'no';
            }
            // and
            // if in array 'no'
            // fm = no
            // else
            // fm = yes

        }
        // and
        if (in_array('no', $fm_a)) {
            $skip_data = true;
        } else {
            $skip_data = false;
        }

        // search string ignores filters
        if ($sif_flag=='no' && $skip_data==true) {
        	continue;
        }

        // search string
        if ($search) {
        	// TODO consider array_search
            // perhaps not required since this freeform text search; not required match atom via $sm_a = array();
			$search_bits_str = trim($search);
			$search_bits_arr = explode(" ", $search);
			//var_dmp($search_bits);
			foreach ($search_bits_arr as $search_val) {
	            foreach($d as $kd => $kv) {
	            	if (is_array($kv)) continue;
	                //if (preg_match("/$search/i", $kv)) {
	               	if (preg_match("/$search_val/i", $kv)) {
	                    // if match, save state and break; no need to try the rest of $d cols
	                    //echo $kv.'-';
	                    $skip_data = false;
	                    break 2;
	                } else {
	                    // if not match, keep trying (looping foreach)
	                    $skip_data = true;
	                }
	            }
            }
        }

        // final blow
        if ($skip_data == false) {
            $ddata[] = $d;
        }
    }

    // Sort
    if (isset($_POST[$ses_srt_name]) && $_POST[$ses_srt_name]) {
    	$_SESSION[$ses_srt_name] = $_POST[$ses_srt_name];
    } else {
    	if (!isset($_SESSION[$ses_srt_name])) {
    		$_SESSION[$ses_srt_name] = $sort_col_def;
    	}
    }
    $sort_col_bits = explode(':', $_SESSION[$ses_srt_name]);
    $sort_col = $sort_col_bits[0];
    $sort_order = $sort_col_bits[1];

    if ($sort_col && $ddata) {
	    foreach ($ddata as $k => $v) {
	    	//$sort_col_array[$k] = $v["$sort_col"];
	    	$sort_col_array[$k] = strtolower($v["$sort_col"]);
	    }

	    if ($sort_order=='desc') {
	    	array_multisort($sort_col_array, SORT_DESC, $ddata);
	    } else {
	    	array_multisort($sort_col_array, SORT_ASC, $ddata);
	    }
    }

    // Sort th html
    $th_html =
    	"<script type='text/javascript'>
			function srt_click(srt_col) {
				document.forms['$formname'].$ses_srt_name.value = srt_col;
				document.forms['$formname'].submit();
			}
		</script>";
    foreach ($table_h as $kname => $vlabel) {
    	$th_html .= '<th>';
    	if (!preg_match('/^null/', $kname)) {
    		// add sort controls
    		$sort_col_sel_asc  = '';
    		$sort_col_sel_desc = '';
    		if ($kname == $sort_col) {
    			if ($sort_order=='asc') {
    				$sort_col_sel_asc  = ' sort_selected';
    			} elseif ($sort_order=='desc') {
    				$sort_col_sel_desc = ' sort_selected';
    			}
    		}
    		$th_html .= "
    			<a href='#' class='sort_asc{$sort_col_sel_asc}' onclick='srt_click(\"$kname:asc\")'>&uarr;</a>
    			<a href='#' class='sort_dsc{$sort_col_sel_desc}' onclick='srt_click(\"$kname:desc\")'>&darr;</a>";
    	}
    	//$out = "<a href='' class='sort_asc sort_selected'>&uarr;</a><a href='' class='sort_dsc'>&darr;</a>";
    	$th_html .= " $vlabel </th>";
    }
    $th_html .= "<input type='hidden' name='$ses_srt_name' />";

    $odata = array();
    //$ddata['data'] = $ddata;
    //$ddata['th_html'] = $th_html;
    //return $ddata;
    $odata['data'] = $ddata;
    $odata['th_html'] = $th_html;
    return $odata;
}/*}}}*/
function dat_pgn($a) {/*{{{*/
	// data pagina
    // pagina   pagination      array
    //			cpn				current page no
    //			ipp				items_per_pg: 10, 20, 30, etc
    //			fmt				format: 	num			Page: 1, 2, 3, ...
    //										alpha		Page: ame-bar, buk-fra, etc
    //			acn				alpha_col_name	if fmt=alpha, then also specify acn

	// Output
	$pdata = array();	// paginated data
	//$pdata['data']	// actual data
	//$pdata['page']	// page nos array

    $a = cst_arr($a, 'dat');
    $data				= $a['dat'];
    $current_page_no 	= cst_def($a, 1, 'cpn');
	$items_per_page  	= cst_def($a, 10, 'ipp');
	$pagina_format	 	= cst_def($a, 'num', 'fmt');
	$pager_txt			= cst_def($a, 'Page: %s', 'ptx');
	$pager_lnk			= cst_def($a, '%l', 'pln');
	$ipp_lnk			= cst_def($a, '', 'iln');
	$cookie_name		= cst_def($a, 'va_dat_pgn_ipp', 'ckn');

	$data_nos = count($data);

	// Items per page
	if (isset($_POST[$cookie_name]) && $_POST[$cookie_name]) {
		setcookie($cookie_name, $_POST[$cookie_name], time() + (60*60*24*365), APP_URI.'/');
		$items_per_page = $_POST[$cookie_name];
	} elseif (!isset($_COOKIE[$cookie_name])) {
		setcookie($cookie_name, $items_per_page, time() + (60*60*24*365), APP_URI.'/');
	} else {
		$items_per_page = $_COOKIE[$cookie_name];
	}

	$ipp_nos = array(10, 20, 50, 100);
	$ipp_html = "Display:";
	foreach ($ipp_nos as $in) {
		if ($in == $items_per_page) {
			$ipp_html .= '<span class="selected">'.$in.'</span>';
		} else {
			$ipp_html .= "<a href='#' onclick='ipp_click($in)'>$in</a>";
		}
	}
	// FIXME apply correction here ipp_html doesn't even need to be a form
	$ipp_html = "
		<script type='text/javascript'>
			function ipp_click(ipp) {
				document.forms['items_per_page'].$cookie_name.value = ipp;
				// FIXME apply correction here, javascript, setcookie, then location.href
				document.forms['items_per_page'].submit();
			}
		</script>
		<div id='ipp'>
		<form name='items_per_page' method='post' action='$ipp_lnk'>".
		$ipp_html." records per page<input type='hidden' name='$cookie_name' value='$items_per_page' />
		</form>
		</div>";


	// If less than item count
	if($data_nos <= $items_per_page) {
		//$pdata['data'] = $data;
		//$pdata['page'] = null;
		//return $pdata;
	}

	// Pager + Pager HTML
	$pager = array();
	$pager_html = '';
	$pager_html_sub = '';
	if ($pagina_format == 'num') {
		$total_pages = ceil($data_nos / $items_per_page);
		if ($total_pages > 0) {
			for ($i=1; $i<=$total_pages; $i++) {
				$pager[] = $i;
				if ($current_page_no == $i) {
					$pager_html_sub .= "<span class='selected'>$i</span>";
					continue;
				}
				$l = preg_replace('/%l/', $i, $pager_lnk);
				$pager_html_sub .= "<a href='$l'>$i</a>";
			}
			$pager_html = preg_replace('/%s/', $pager_html_sub, $pager_txt);
			$pager_html = '<div id="pager">'.$pager_html.'</div>';
		}
	} elseif ($pagina_format == 'alpha') {
		$alpha_col_name = $a['acn'];

	}

	// Data Slice
	$data_slice = array_slice($data, (($current_page_no - 1) * $items_per_page), $items_per_page);

	$pdata['data'] = $data_slice;
	$pdata['pager'] = $pager;
	$pdata['pager_html'] = $pager_html;
	$pdata['result_html'] = htm_res($data_nos);
	$pdata['ipp_html'] = $ipp_html;

	return $pdata;
}/*}}}*/
function dba_col($a) {/*{{{*/
    // {{{
    // $a   table name
    // provides full column information on a table
    // Field, Type, Null, Key, Default, Extra
    // array(6) {
    //     ["Field"]=>
    //     string(2) "id"
    //     ["Type"]=>
    //     string(7) "int(11)"
    //     ["Null"]=>
    //     string(2) "NO"
    //     ["Key"]=>
    //     string(3) "PRI"
    //     ["Default"]=>
    //     NULL
    //     ["Extra"]=>
    //     string(14) "auto_increment"
    //   }
    // }}}
    $table = $a;
    $sql = "show columns from `$table`";
    $result = mysql_query($sql);
    $column = array();
    while ($col = mysql_fetch_assoc($result)) {
        $column[] = $col;
    }
    return $column;
}/*}}}*/
function dba_get_cell($query) {/*{{{*/
	$result = mysql_query($query);
	$row = mysql_fetch_assoc($result);

	preg_match('/select\s+.*\s+from/i', $query, $match);
	$cell = $match[0];
	$cell = preg_replace('/select\s+/i', '', $cell);
	$cell = preg_replace('/\s+from/i', '', $cell);

	return $row[$cell];
}/*}}}*/
function dba_sel($a) {/*{{{*/
    $a = cst_arr($a, 'sql');
    $sql = $a['sql'];
    $result = mysql_query($sql);

    // field
    $field = array();
    $field_count = mysql_num_fields($result);
    for ($i=0; $i<$field_count; $i++) {
        $field_tmp['type']  = mysql_field_type($result, $i);
        $field_tmp['name']  = mysql_field_name($result, $i);
        $field_tmp['len']   = mysql_field_len($result, $i);
        $field_tmp['flags'] = mysql_field_flags($result, $i);
        $field[] = $field_tmp;
        unset($field_tmp);
    }
    $r['field'] = $field;

    // data
    $data = array();
    while ($row = mysql_fetch_assoc($result)) {
        $row_he = array();
        foreach ($row as $kcol_name => $vcol_value) {
            $row_he[$kcol_name] = va_html_ent($vcol_value);
        }
        $data[] = $row_he;
        //$data[] = $row;
    }
    $r['data'] = $data;
    return $r;
}/*}}}*/
function dba_insupdel_id($new_post_ids, $old_db_ids) {/*{{{*/
    // $old_db_ids is an array with 'id'
    // $new_post_ids is an array with 'id'
    // return 2 arrays
    //      $o['insert'] = array('id')
    //      $o['delete'] = array('id')
    //      update doesn't need to be returned - nothing to do
    // example: an admin is assigned various categories ('id' - category_id)
    // table: | id | admin_id | category_id |
    $o['insert'] = $o['delete'] = array();
    if (!is_array($old_db_ids) || !is_array($new_post_ids)) return false;
    foreach ($new_post_ids as $n_id) {
        if (!in_array($n_id, $old_db_ids)) $o['insert'][] = $n_id;
    }
    foreach ($old_db_ids as $o_id) {
        if (!in_array($o_id, $new_post_ids)) $o['delete'][] = $o_id;
    }
    return $o;
}/*}}}*/
function dba_id2x($a) {/*{{{*/
	// database id 2 x where x is field or fields in a string, id is a single id
	$id = $a['id'];
	// field = 'name'
	// field = 'name, email, country'
	$field = $a['field'];
	$table = $a['table'];
	$query = "select $field from $table where id=$id";
	$result = mysql_query($query);
	$row = mysql_fetch_assoc($result);
	return $row;
}/*}}}*/
function dba_insupdel_assoc($new_post_data, $old_db_data) {/*{{{*/
    // $old_db_data is an assoc array with   'id' and 'value'
    // $new_post_data is an assoc array with 'id' and 'value'
    // return 3 assoc arrays
    //      $o['insert'][id] = 'value'
    //      $o['update'][id] = 'value'
    //      $o['delete'][]   = 'id'
    // (note): to delete don't assume absense of id in $new_post_data. pass id and assign NULL value
    // example: a partner (fixed partner_id) submits scores ('value' - score) for various cvs ('id' - cv_id)
    // table: | id | partner_id | cv_id | score |
    /*{{{*/ /*Example code use
    $partner_id = partner_id('');
    $new_post_data = $_POST['eval_cv'];

    $db =& JFactory::getDBO();
    $sql = "select cv_id, evaluation_score from #__rfs_cv_evaluation where partner_id=$partner_id";
    $db->setQuery($sql);
    $rows = $db->loadAssocList();
    $old_db_data = array();
    foreach ($rows as $r) {
        $old_db_data[$r['cv_id']] = $r['evaluation_score'];
    }

    $task_db_data = dba_insupdel_assoc($new_post_data, $old_db_data);

    //echo '<pre>';
    //var_dump($new_post_data);
    //var_dump($old_db_data);
    //var_dump($task_db_data);
    //echo '</pre>';

    if (is_array($task_db_data['insert'])) {
        foreach ($task_db_data['insert'] as $cv_id => $eval_score) {
            $cv = new stdClass();
            $cv->cv_id = $cv_id;
            $cv->partner_id = $partner_id;
            $cv->evaluation_score = $eval_score;
            $db->insertObject('#__rfs_cv_evaluation', $cv);
        }
    }
    if (is_array($task_db_data['update'])) {
        foreach ($task_db_data['update'] as $cv_id => $eval_score) {
            $sql = "update #__rfs_cv_evaluation set evaluation_score=$eval_score where cv_id=$cv_id && partner_id=$partner_id";
            $db->setQuery($sql);
            $db->query();
        }
    }
    if (is_array($task_db_data['delete'])) {
        foreach ($task_db_data['delete'] as $cv_id) {
            $sql = "delete from #__rfs_cv_evaluation where cv_id=$cv_id && partner_id=$partner_id";
            $db->setQuery($sql);
            $db->query();
        }
    }
    */ /*}}}*/
    $o['insert'] = $o['update'] = $o['delete'] = array();
    if (!is_array($old_db_data) || !is_array($new_post_data)) return false;
    foreach ($new_post_data as $n_id => $n_value) {
        if (in_array($n_id, array_keys($old_db_data))) {
            // update, delete, or do nothing
            if ($new_post_data[$n_id] != $old_post_data[$n_id]) {
                $o['update'][$n_id] = $new_post_data[$n_id];
            } elseif (!$new_post_data[$n_id]) {
                $o['delete'][] = $n_id;
            }
        } else {
            // insert
            $o['insert'][$n_id] = $new_post_data[$n_id];
        }
    }
    return $o;
}/*}}}*/
function dba_rec_par($a) {/*{{{*/
	// Database recursive parent
	// The table recursed must have the following fields
	// 		parent
	//		sequence
	// Returns an ordered (sequence) list of items in their respective parental hierarchy
	global $_dba_rec_par;

	$table = $a['table'];
	$extra_sql = cst_def($a, '', 'extra_sql');
	$parent_id = cst_def($a, 0, 'parent_id');
	$parent_col_name = cst_def($a, 'parent', 'parent_col_name');
	$sequence_col_name = cst_def($a, 'sequence', 'sequence_col_name');

	$_dba_rec_par['sql'] = "select * from `$table`
		where `$parent_col_name`=<%parent_id%>
		$extra_sql order by `$sequence_col_name`";

	return dba_rec_par_0($parent_id);
}
function dba_rec_par_0($parent_id) {
	global $_dba_rec_par;
	$sql = preg_replace('/<%parent_id%>/', "$parent_id", $_dba_rec_par['sql']);
	$res = mysql_query($sql);
	$c = array();
    while($row = mysql_fetch_assoc($res)) {
        //$c[] = array($row['category_name'] => $row['id']);
        //$c[] = va_cat2($row['id']);

        //$c[$row['menu_title']] = $row['id'];
        //$c = array_merge($c, dba_rec_par_0($row['id']));
        $tmp = array(
        	'id'			=>	$row['id'],
        	'menu_title'	=>	$row['menu_title'],
        	);
        //$c[] = $tmp;
        $c[] = array_merge($tmp, dba_rec_par_0($row['id']));

        //$c[] = dba_rec_par_0($row['id']);
    }
	return $c;
}/*}}}*/
function dba_rel_2id($a) {/*{{{*/
    // dba (database) rel (relation) good for relation tables
    // relation maintained via one base id and the other variable id - the 2 ids
    // bid = base id
    // vid = variable id
    $table = $a['table'];
    $bid_col = $a['bid_col'];
    $bid_val = $a['bid_val'];
    $vid_col = $a['vid_col'];
    $vid_val_array = $a['vid_val'];

    $res = mysql_query("select * from `$table` where `$bid_col`=$bid_val");
    $vid_val_array_db = array();
    while ($row = mysql_fetch_assoc($res)) {
        $vid_val_array_db[] = $row["$vid_col"];
    }
    // insert
    foreach ($vid_val_array as $vid) {
        if (in_array($vid, $vid_val_array_db)) { continue; }
        $sql_ins = "insert into `$table` ($bid_col, $vid_col) values ($bid_val, $vid)";
        mysql_query($sql_ins);
    }
    // delete
    foreach ($vid_val_array_db as $vid_db) {
        if (in_array($vid_db, $vid_val_array)) { continue; }
        $sql_del = "delete from `$table` where (`$vid_col`=$vid_db && $bid_col=$bid_val)";
        mysql_query($sql_del);
    }

}/*}}}*/
function dba_sis($a) {/*{{{*/
    // smart insert
    // table    table name
    // post     $_POST
    $a = cst_arr($a);
    $table = $a['name'];
    $col_info = dba_col($table);
    foreach ($col_info as $c) {
        $col_name = $c['Field'];
        $col_type = $c['Type'];
        // skip column 'id'
        if ($col_name == 'id') { continue; }
        $col_value = dba_san(array(
            'value' =>  $_POST[$col_name],
            'type'  =>  $col_type,
            ));
        // stripslashes, mysql_real_escape_string
        $col_value = dba_esc_str($col_value);
        $scol_names  .= "`$col_name`, ";
        $scol_values .= "'$col_value', ";
    }
    $scol_names  = preg_replace('/,\s$/', '', $scol_names);
    $scol_values = preg_replace('/,\s$/', '', $scol_values);
    $sql = "insert into `$table` ($scol_names) values ($scol_values)";
    mysql_query($sql);
    $last_insert_id = mysql_insert_id();
    return $last_insert_id;
}/*}}}*/
function dba_sif($a) {/*{{{*/
    // table    table name
    $table = $a;
    $col_info = dba_col($table);
    foreach ($col_info as $c) {
        $col_name = $c['Field'];
        if ($col_name == 'id') { continue; }
        if ($col_name == 'data') {
            $col_value = file_get_contents($_FILES[$table]['tmp_name']);
            // NO stripslashes, ONLY mysql_real_escape_string
            $col_value = mysql_real_escape_string($col_value);
        } elseif ($_FILES[$table][$col_name]) {
            $col_value = $_FILES[$table][$col_name];
            // stripslashes, mysql_real_escape_string
            $col_value = dba_esc_str($col_value);
        } elseif ($_POST[$col_name]) {
            $col_value = $_POST[$col_name];
            // stripslashes, mysql_real_escape_string
            $col_value = dba_esc_str($col_value);
        }
        $scol_names  .= "`$col_name`, ";
        $scol_values .= "'$col_value', ";
    }
    $scol_names  = preg_replace('/,\s$/', '', $scol_names);
    $scol_values = preg_replace('/,\s$/', '', $scol_values);
    $sql = "insert into `$table` ($scol_names) values ($scol_values)";
    mysql_query($sql);
    $last_insert_id = mysql_insert_id();
    return $last_insert_id;
}/*}}}*/
function dba_san($a) {/*{{{*/
    // sanitize $_POST or $_FILE values before inserting into database/*{{{*/
    // create emty default values for appropriate column types
    // Args:
    // value    post value
    // type     column type
    // /*}}}*/
    $value = $a['value'];
    $type  = $a['type'];

    // convert arrays to scalars eg. country array('th', 'vn', 'us') convert to scalar 'th, vn, us'
    if (is_array($value)) {
        $value = implode(",", $value);
        return $value;
    }

    // empty default date 0000-00-00
    if (!$value && $type=='date') {
        $value = '0000-00-00';
        return $value;
    }

    return $value;
}/*}}}*/
function dba_esc_str($a) {/*{{{*/
    if (get_magic_quotes_gpc()) {
        $a = stripslashes($a);
    }
    $a = mysql_real_escape_string($a);
    return $a;
}/*}}}*/
function dbg_() {/*{{{*/

}/*}}}*/
function dt_mysql2human($a) {/*{{{*/
    $a = cst_arr($a, 'dt');
    $dt = $a['dt'];
    $format = cst_def($a, 'eu', 'format');

    // date only
    $do = $a['do'];

    // presence of : indicates timestamp is also included
    if (preg_match('/\:/', $dt)) {
		$time_format = ' \a\t g:i A';
    } else {
    	$time_format = '';
    }

    if ($do=='yes') {
    	$time_format = '';
    }

    switch ($format) {
        case 'eu':
            $dt_format = "d M Y{$time_format}";
            break;
        case 'us':
            $dt_format = "M j, Y{$time_format}";
            break;
    }

    $dt_human = date($dt_format, strtotime($dt));
    return $dt_human;
}/*}}}*/
function dt_mysql_now($then='') {/*{{{*/
	if ($then) {
		return date('Y-m-d H:i:s', $then);
	} else {
    	return date('Y-m-d H:i:s');
	}
}/*}}}*/
function dtm_cur_msl() {/*{{{*/
    // datetime current mysql, mysql date time
    // better name: dtm_now_msq
    // better name: dt_now_mysql
    // alt: mysql_dt_time
    return date('Y-m-d H:i:s');
}/*}}}*/
function dir_ls($a=array()) {/*{{{*/
    // {{{ Input:
    // $a[excludes]    list of files to be excluded from directory list
    //                 accepts '*' for pattern match
	// $a[dir]         directory that is to be traversed
    // }}}
    // {{{ Output:
    // $oa_dir_ls = [foo.php, bar.php, baz.php] }}}
    // {{{ Example:
    // $excludes = array(
    // 		'.',
    // 		'..',
    // 		'*.swp',
    //       '*.txt',
    // 		'init.php',
    // 	);
    // 	$dir = $_SERVER['DOCUMENT_ROOT'].'/lib/kafe';
    // 	$include_files = dir_ls(array(
    // 		'excludes'	=>	$excludes,
    // 		'dir'		=>	$dir,
	// ));
    // foreach ($include_files as $f) { include_once($f); }
    // }}}

	// excludes: $excludes, $excludes_exact, $excludes_fuzzy
	$excludes=array();
	if (isset($a['excludes']) && $a['excludes']) { $excludes = $a['excludes']; }
	// excludes with exact match filename
	// excludes with pattern match, currently only '*'
	$excludes_exact = array();
	$excludes_fuzzy = array();
	//for ($i=0; $i <= count($excludes); $i++) {
	for ($i=0; $i < count($excludes); $i++) {
		if(preg_match("/\*/", $excludes[$i])) {
			$excludes_fuzzy[]=$excludes[$i];
		} else {
			$excludes_exact[]=$excludes[$i];
		}
	}
	$dir='';
	if(isset($a['dir']) && $a['dir']) { $dir=$a['dir']; }
	$oa_dir_ls=array();
	$d = dir($dir);
	while(false !== ($entry=$d->read())) {
		// bail out and continue, separating exact and fuzzy should be faster
		// bail out for exact excludes
		if(in_array($entry, $excludes_exact)) { continue; }
		// bail out for pattern matched excludes
		foreach($excludes_fuzzy as $value) {
			$value=preg_replace("/\*/", ".*", $value);
			$value=preg_replace("/\./", "\.", $value);
			if(preg_match("/$value/", $entry)) { continue 2; }
		}
		$oa_dir_ls[]=$entry;
	}
	$d->close();
	return $oa_dir_ls;
}/*}}}*/
function dir_mod_inc($a) {/*{{{*/
    $a = cst_arr($a, 'dir');
    $dir = cst_def($a, '', 'dir');
    //$excludes = cst_def($a['excludes'], array());
    $excludes = cst_def($a, array(), 'excludes');

    $excludes_def = array('.', '..', '_inactive', '*svn', '*swp', '*~', '*admin');
    $excludes = array_merge($excludes, $excludes_def);

    $dir_ls_inc = dir_ls(array(
        'dir'       =>  $dir,
        'excludes'  =>  $excludes,
        ));

    foreach ($dir_ls_inc as $d) {
        include_once("$dir/$d");
    }
    return true;
}/*}}}*/
function dir_mod_inc_adm($a) {/*{{{*/
    $a = cst_arr($a, 'dir');
    $dir = cst_def($a, '', 'dir');
    //$excludes = cst_def($a['excludes'], array());
    $excludes = cst_def($a, array(), 'excludes');

    $excludes_def = array('.', '..', '_inactive', '*svn', '*swp', '*~');
    $excludes = array_merge($excludes, $excludes_def);

    $dir_ls_inc = dir_ls(array(
        'dir'       =>  $dir,
        'excludes'  =>  $excludes,
        ));

    foreach ($dir_ls_inc as $d) {
        // only include admin files and functions
        if (!preg_match("/admin/", $d)) {
            continue;
        }
        include_once("$dir/$d");
    }
    return true;

}/*}}}*/
function doc_() {/*{{{*/

}/*}}}*/
function frm_dsp($a=array()) {/*{{{*/
    // {{{ Args
    // name         used in form name, table id, layout name
    // method       get or post
    // action       action URL
	// fcontrols    form controls - text, textarea, select, radio, etc
	// layout	    array   rc_type, rc_nos, flow
    // process      point to another function, if empty then redisplay form (like update, apply)
    // }}}
    // {{{ Example
    // echo frm_dsp(array(
    //     'name'	=>	'contact_form',
    //     'elements'	=>	array(
    //         frm_txt(array(
    //             'label'	=>	'Your Name',
    //             'name'	=>	'your_name', )),
    //         frm_txt(array(
    //             'label'	=>	'Your Email',
    //             'name'	=>	'your_email', )),
    //         frm_txt(array(
    //             'label'	=>	'Address',
    //             'name'	=>	'address', )),
    //         frm_txt(array(
    //             'label'	=>	'Country',
    //             'name'	=>	'country',
    //             'help'	=>	'Your country of residence', )),
    //     ),
    //     'layout'	=>	array(
    //         'rc_type'	=>	'cols',
    //         'rc_nos'	=>	'1',
    //         'flow'		=>	'accross',
    //     ),
    // ));
    // }}}
    // {{{ Form Process
    // 1. Assign args
    // 2. Get values
    //      a. $_POST
    //      b. default values
    //          i. database
    //      c. null
    // 3. Validate
    // 4. Process
    // 5. Output
    // }}}
	//$name   = ''; if (isset($a['name']) && $a['name']) { $name = $a['name']; }
    $name = cst_def($a, '', 'name');
    //$method = 'post'; if (isset($a['method']) && $a['method']) { $method = $a['method']; }
    $method = cst_def($a, 'post', 'method');
    //$action = ''; if (isset($a['action']) && $a['action']) { $action = $a['action']; }
    $action = cst_def($a, '', 'action');
    //$process = $name.'_process'; if (isset($a['process']) && $a['process']) { $process = $a['process']; }
    $process = cst_def($a, $name.'_process', 'process');
	$layout = cst_def($a, array(), 'layout');
    // table, div
    $layout_style = cst_def($a, 'table', 'layout_style');
    $template = cst_def($a, '', 'template');

    // Elements
    // $a['elements'] is an array with output, bvalid
	//$elements = $a['elements'];
    // ? consider pulling frm_xxx() functions inside frm_dsp()
    // -- no need because it might well be necessary to loop elements before putting in form (like mult edit items)
    $e_output = array();

    if (!$a['elements']) { $a['elements'] = array(array('bvalid'=>true,'output'=>'')); }
    foreach ($a['elements'] as $e) {
        $e_output[] = $e['output'];
        $e_bvalid[] = $e['bvalid'];
    }

    $output = '';
    // post-self url && validation check
    if (POST_SELF && !in_array(false, $e_bvalid)) {
        if (function_exists("$process")) {
            $output .= $process();
        }
    }
    // to prevent form re-display, rediret in $process() above
    $output .= "\n<form name=\"$name\" enctype=\"multipart/form-data\" method=\"$method\" action=\"$action\" id=\"$name\">";
    if ($template) {
    	$template_content = file_get_contents($template);

    	foreach ($e_output as $e_output_html) {
    		preg_match('/<div id=".*"/', $e_output_html, $fctrl_name);
    		preg_match('/<div class="flabel">.*<\/div>/', $e_output_html, $label);
    		preg_match('/<div class="fctrl">.*<\/div>/', $e_output_html, $fctrl);
    		preg_match('/<div class="femsg">.*<\/div>/', $e_output_html, $femsg);
    		preg_match('/<div class="fhelp">.*<\/div>/', $e_output_html, $fhelp);

			$fctrl_name = preg_replace('/<div id="(.*)"/', '$1', $fctrl_name[0]);
			$fctrl_name = preg_replace('/_mol$/', '', $fctrl_name);

    		$label = preg_replace('/<div class="flabel">(.*)<\/div>/', '$1', $label[0]);
    		$fctrl = preg_replace('/<div class="fctrl">(.*)<\/div>/', '$1', $fctrl[0]);
    		$femsg = preg_replace('/<div class="femsg">(.*)<\/div>/', '$1', $femsg[0]);
    		$fhelp = preg_replace('/<div class="fhelp">(.*)<\/div>/', '$1', $fhelp[0]);

    		$template_content = preg_replace("/<%[\s]*{$fctrl_name}_label[\s]*%>/", $label, $template_content);
    		$template_content = preg_replace("/<%[\s]*{$fctrl_name}_fctrl[\s]*%>/", $fctrl, $template_content);
    		$template_content = preg_replace("/<%[\s]*{$fctrl_name}_femsg[\s]*%>/", $femsg, $template_content);
    		$template_content = preg_replace("/<%[\s]*{$fctrl_name}_fhelp[\s]*%>/", $fhelp, $template_content);
    	}
    	$output .= $template_content;

    } else if ($layout_style=='table') {
    	if (!isset($layout['rc_type'])) $layout['rc_type']='';
    	if (!isset($layout['rc_nos'])) $layout['rc_nos']='';
    	if (!isset($layout['flow'])) $layout['flow']='';
        $output .= lyt_rc(array(
            'name'	    =>	"table_$name",
            'data'	    =>	$e_output,
            'rc_type'	=>	$layout['rc_type'],
            'rc_nos'	=>	$layout['rc_nos'],
            'flow'		=>	$layout['flow'],
        ));
    } else {
        foreach ($e_output as $eo) {
            $output .= $eo;
        }
    }
    $output .= "</form>\n";

    return $output;
}/*}}}*/
function frm_val($a=array()) {/*{{{*/
    $name    = $a['name'];
    $default = $a['default'];
    // get $_POST value, important $_POST data value will always over ride any default value
    // used in form elements only: frm_txt, frm_sel, etc, if needed anywhere else get $_POST directly
    // else get $default_value
    // else get null value
    // stripslashes ie va_str_sla needs to be applied to $_POST data
    if (POST_SELF) {
        if (preg_match('/\[.+\]/', $name)) {
            // used in frm edit
            // $data[id][field_name]
            $eval_array = '$rvar = $'.$name.';';
            //$eval_array = '$rvar = va_str_sla($'.$name.');';
            // default value for multi-dimenstional form array is $_POST['data']
            $data = $_POST['data'];
            eval($eval_array);
            if (is_array($rvar)) {
                $rvar_ss = array();
                foreach($rvar as $key => $value) {
                    $key = va_str_sla($key);
                    $key = va_html_ent($key);
                    $value = va_str_sla($value);
                    $value = va_html_ent($value);
                    $rvar_ss[$key] = $value;
                }
                $rvar = $rvar_ss;
            } else {
                $rvar = va_str_sla($rvar);
                $rvar = va_html_ent($rvar, ENT_QUOTES, 'UTF-8');
            }
            return $rvar;
        } else {
            // used in frm_sel, new form creation, and more
            $rvar = $_POST[$name];
            if (is_array($rvar)) {
                $rvar_ss = array();
                foreach($rvar as $key => $value) {
                    $key = va_str_sla($key);
                    $key = va_html_ent($key);
                    $value = va_str_sla($value);
                    $value = va_html_ent($value);
                    $rvar_ss[$key] = $value;
                }
                $rvar = $rvar_ss;
            } else {
                $rvar = va_str_sla($rvar);
                $rvar = va_html_ent($rvar);
            }
            return $rvar;
        }
    } elseif ($default) {
        return $default;
    } elseif ($default=='0') {
        return '0';
    } else {
        return '';
    }
}/*}}}*/
function frm_vaf($a=array()) {/*{{{*/
    // get $_FILE value
    //
}/*}}}*/
function frm_vad($a) {/*{{{*/
    // a form validation layer that does 2 things
    // 1. executes validation function (this returns either true or false)
    // 2. provides error message (actual validation function does not provide error message)

    $fn_valid = $a['fn_valid'];
    // $fn_emsg could be array or a string - Perlish hell ya :)
    // if validation result returns an error code string then $fn_emsg is array
    $fn_emsg  = $a['fn_emsg'];
    $value = $a['value'];
    $fn_valid_result = true;
    //if ($_POST && function_exists($fn_valid)) {
    if (POST_SELF && function_exists($fn_valid)) {
        $fn_valid_result = $fn_valid($value);
    }
    // a single validation function is ok as long as we can track different error messages
    // for multiple error tracking validation function will always return true (it will send
	// an error code string
	if (preg_match('/^error_/', $fn_valid_result)) {
		// handle error
		$error_msg_code = preg_replace('/^error_/', '', $fn_valid_result);
		$msg = $fn_emsg[$error_msg_code];
		$valid = false;
	} elseif ($fn_valid_result==true) {
		$msg = '';
		$valid = true;
	} else {
		$msg = $fn_emsg;
		$valid = false;
	}

	/*
    if ($valid == true) {
        $msg = '';
    } else {
        $msg = $fn_emsg;
    }
    */

    $r['bvalid'] = $valid;
    $r['emsg'] = $msg;
    return $r;
}/*}}}*/
function frm_hybrid_date($a=array()) {
	$name = $a['name'];
	$label = $a['label'];
	$default = explode('-', $a['default']);
	$d['year'] = $default[0];
	$d['month'] = $default[1];
	$d['day'] = $default[2];

	// month sel date sel year txt
	$month = array(
		'Jan'	=>	'01',
		'Feb'	=>	'02',
		'Mar'	=>	'03',
		'Apr'	=>	'04',
		'May'	=>	'05',
		'Jun'	=>	'06',
		'Jul'	=>	'07',
		'Aug'	=>	'08',
		'Sep'	=>	'09',
		'Oct'	=>	'10',
		'Nov'	=>	'11',
		'Dec'	=>	'12',
	);
	$day = range(1, 31);
	
	// ctrl month
	$ctrl_month_html = "<select name='{$name}[month]'>";
	foreach ($month as $key => $val) {
		$selected = '';
		if ($val==$d['month']) $selected = 'selected';
		$ctrl_month_html .= "<option value='$val' $selected>$key</option>";
	}
	$ctrl_month_html .= "</select>";
	
	// ctrl day
	$ctrl_day_html = "<select name='{$name}[day]'>";
	foreach ($day as $val) {
		$val = preg_replace('/^(\d)$/', '0$1', $val);
		$selected = '';
		if ($val==$d['day']) $selected = 'selected';		
		$ctrl_day_html .= "<option value='$val' $selected>$val</option>";
	}
	$ctrl_day_html .= "</select>";
	
	// ctrl year
	$ctrl_year_html = "<input type='text' name='{$name}[year]' size='4' maxlength='4' value='$d[year]' />";
	
	$ctrl_date_html = $ctrl_day_html.$ctrl_month_html.$ctrl_year_html;
	
	$r['bvalid'] = true;
	$r['output'] = frm_out(array(
	    'name'  =>  $name,
	    'label' =>  $label,
	    'ctrl'  =>  $ctrl_date_html,
	    'msg'   =>  '',
	    'help'  =>  $help,
	));
	// Return
	// $r['output']
	// $r['bvalid']
	return $r;	
}
function frm_hybrid_date_process($a) {
	// $a (scalar) combine format (date)
	return "{$a[year]}-{$a[month]}-{$a[day]}";
}
function frm_txt($a) {/*{{{*/
    // {{{ Args
	// name		form name
	// label	label
	// type		plain, date
	// size		int
	// disabled	true, false
    // fvalid   array validation formula
    // }}}
    $a = cst_arr($a);
	$name  = $a['name'];
    $label = cst_lbl(array(
        'name'  =>  $name,
        'label' =>  $a['label'],
        ));
	$help    = cst_def($a, '', 'help');
    $default = $a['default'];

    $size = cst_def($a, '40', 'size');
    //if (isset($a['size']) && $a['size']) { $size = $a['size']; }
    $extra = cst_def($a, '', 'extra');

    // how to use safe_edit
    //        'safe_edit' =>  "yes",
    //        'extra'     =>  ' style="background: gainsboro; color: dimgray;" '
    $safe_edit = cst_def($a, '', 'safe_edit');
    if ($safe_edit) {
        $js_name = $name;
        $js_name = preg_replace('/\[/', '_', $js_name);
        $js_name = preg_replace('/\]/', '_', $js_name);
        $safe_edit_disabled = ' readonly ';
        $safe_edit_cbx_jas = <<<EOT
<input name="tse_cbx_$js_name" type="checkbox" onclick="tse_jas_$js_name()" />
<script type='text/javascript'>
function tse_jas_$js_name() {
    //tse   toggle safe edit
    if (document.getElementById('$name').readonly==true) {
    //if (document.getElementById('$name').readonly==true) {
        document.getElementById('$name').style.backgroundColor = 'gainsboro';
        document.getElementById('$name').style.color = 'dimgray';
        document.getElementById('$name').readonly = false;
        document.getElementById('$name').setAttribute('readOnly', 'readonly');
    } else {
        document.getElementById('$name').style.backgroundColor = 'white';
        document.getElementById('$name').style.color = 'black';
        document.getElementById('$name').readonly = true;
        document.getElementById('$name').removeAttribute('readOnly');
    }
}
</script>
EOT;
    } else {
    	$safe_edit_disabled = '';
    	$safe_edit_cbx_jas = '';
    }

    // value
    $value = frm_val(array(
        'name'      =>  $name,
        'default'   =>  $default,
    ));

    // validation: single validation or multiple validation -- $a['fvalid'] or $a['fvalid_array']
    // $vresult['bvalid'], $vresult['emsg']
    if (isset($a['fvalid_array']) && $a['fvalid_array']) {
        foreach ($a['fvalid_array'] as $fvalid) {
            $vresult = frm_vad(array(
                'fn_valid'  =>  $fvalid['name'],
                // note: emsg is redundant - u get back exactly what u put in - or empty if bvalid is true
                'fn_emsg'   =>  $fvalid['emsg'],
                'value'     =>  $value,
                ));
            $r['bvalid'] = $vresult['bvalid'];
            if ($r['bvalid']==false) break;
        }
    } else {
    	if (!isset($a['fvalid']['name'])) $a['fvalid']['name']='';
    	if (!isset($a['fvalid']['emsg'])) $a['fvalid']['emsg']='';
        $vresult = frm_vad(array(
            'fn_valid'  =>  $a['fvalid']['name'],
            // note: emsg is redundant - u get back exactly what u put in - or empty if bvalid is true
            'fn_emsg'   =>  $a['fvalid']['emsg'],
            'value'     =>  $value,
            ));
        $r['bvalid'] = $vresult['bvalid'];
    }

    $output = frm_out(array(
        'name'  =>  $name,
        'label' =>  $label,
        'ctrl'  =>  "<input type=\"text\" name=\"$name\" id=\"$name\" size=\"$size\" value=\"$value\" $extra $safe_edit_disabled /> $safe_edit_cbx_jas",
        'msg'   =>  $vresult['emsg'],
        'help'  =>  $help,
    ));
    // Return
    // $r['output']
    // $r['bvalid']
    $r['output'] = $output;

    return $r;
}/*}}}*/
function frm_txd($a=array()) {/*{{{*/
    $name  = $a['name'];
    $label = $a['label'];
    $help    = cst_def($a, '', 'help');
    $default = $a['default'];

    $size = cst_def($a, 20, 'size');
    //if (isset($a['size']) && $a['size']) { $size = $a['size']; }

    $value = frm_val(array(
        'name'  =>  $name,
        'default'   =>  $default,
        ));

    $jas_cal_trg = jas_cal_trg(array( 'name'  =>  $name,));

    $r['bvalid'] = true;

    $r['output'] = frm_out(array(
        'name'  =>  $name,
        'label' =>  $label,
        'ctrl'  =>  "<input type=\"text\" name=\"$name\" id=\"$name\" size=\"$size\" value=\"$value\" $jas_cal_trg />",
        'msg'   =>  $msg,
        'help'  =>  $help,
    ));

    return $r;
}/*}}}*/
function frm_txp($a) {/*{{{*/
    $name  = $a['name'];
    $label = $a['label'];
    $help    = cst_def($a, '', 'help');
    $default = $a['default'];

    $size = cst_def($a, 40, 'size');
    //if (isset($a['size']) && $a['size']) { $size = $a['size']; }

    $extra = cst_def($a, '', 'extra');

    $value = frm_val(array(
        'name'  =>  $name,
        'default'   =>  $default,
        ));

    // validation
    $vresult = frm_vad(array(
        'fn_valid'  =>  $a['fvalid']['name'],
        'fn_emsg'   =>  $a['fvalid']['emsg'],
        'value'     =>  $value,
        ));
    $r['bvalid'] = $vresult['bvalid'];
    //$r['bvalid'] = true;

    $r['output'] = frm_out(array(
        'name'  =>  $name,
        'label' =>  $label,
        'ctrl'  =>  "<input type=\"password\" name=\"$name\" id=\"$name\" size=\"$size\" value=\"$value\" $extra />",
        'msg'   =>  $vresult['emsg'],
        'help'  =>  $help,
    ));

    return $r;
}/*}}}*/
function frm_txa($a) {/*{{{*/
    // {{{ Args
    // name		form name
	// type		plain, rich
	// cols
	// rows
    // }}}
    $a = cst_arr($a);
	$name  = $a['name'];
    $label = cst_lbl(array(
        'name'  =>  $name,
        'label' =>  $a['label'],
        ));
	$help    = cst_def($a, '', 'help');
    $rows = 5;
	if (isset($a['rows']) && $a['rows']) { $rows = $a['rows']; }
    $cols = 40;
	if (isset($a['cols']) && $a['cols']) { $cols = $a['cols']; }

    $default = $a['default'];

    $value = frm_val(array(
        'name'      =>  $name,
        'default'   =>  $default,
        ));

    // validation
    $vresult = frm_vad(array(
        'fn_valid'  =>  $a['fvalid']['name'],
        'fn_emsg'   =>  $a['fvalid']['emsg'],
        'value'     =>  $value,
        ));
    $r['bvalid'] = $vresult['bvalid'];

    $r['output'] = frm_out(array(
        'name'  =>  $name,
        'label' =>  $label,
        'ctrl'  =>  "<textarea name=\"$name\" id=\"$name\" rows=\"$rows\" cols=\"$cols\">$value</textarea>",
        'msg'   =>  $vresult['emsg'],
        'help'  =>  $help,
    ));

    return $r;
}/*}}}*/
function frm_sel($a=array()) {/*{{{*/
    // {{{ Args
    //      name        used in select name id
    //      label       caption / label of control ctrl
    //      help        help message
    //      size
    //      multiple
    //      default
    //      lvp_sel     label value pair
    // }}}
 	$name = $a['name'];
    $label = cst_lbl(array(
        'name'  =>  $name,
        'label' =>  $a['label'],
        ));
	$help    = cst_def($a, '', 'help');

    $default = $a['default'];
    //if (!is_array($default)) { $default = array($default); }

    $size = 1;
    if (isset($a['size']) && $a['size']) { $size = $a['size']; }

    $multiple = '';
    if (isset($a['multiple']) && $a['multiple']) { $multiple = 'multiple'; }

    // however, never make a select control disabled (or any control with the possibility of multiple values)
    $extra = cst_def($a, '', 'extra');

    // {{{ Construct select options
    // input $lvp_sel[$label] =$value
    //       $lvp_sel[$label]['active']=true
    // construct <option $value>$label</option>
    // }}}
    $lvp_sel = $a['lvp_sel'];

    $select_opts = "";
    if ($a['pls_sel']) {
        $select_opts = "<option value= ''>$a[pls_sel]</option>\n";
    }

    $values = frm_val(array(
        'name'      =>  $name,
        'default'   =>  $default,
        ));
    // $values needs to be an array for in_array below
    if ($values=='') {
        $values = array();
    // POST_SELF will all ready return an array name[]
    } elseif (!is_array($values)) {
        $values = array($values);
    }
    foreach ($lvp_sel as $k_sel_label => $v_sel_value) {
        $selected = '';
        if (in_array($v_sel_value, $values)) { $selected = 'selected'; }
        $select_opts .= "<option value=\"$v_sel_value\" $selected >$k_sel_label</option>\n";
    }

    $r['bvalid'] = true;

    $r['output'] = frm_out(array(
        'name'  =>  $name,
        'label' =>  $label,
        'ctrl'  =>  "<select name=\"{$name}[]\" id=\"$name\" size=\"$size\" $multiple {$extra}>\n $select_opts </select>\n",
        //'ctrl'  =>  "<select name=\"{$name}\" id=\"$name\" size=\"$size\" $multiple>\n $select_opts </select>\n",
        'msg'   =>  '',
        'help'  =>  $help,
    ));

    return $r;
}/*}}}*/
function frm_cbx($a=array()) {/*{{{*/
    // {{{ Args
    // name        used in container div id, actual checkbox name/id will come from lvp_cbx
    // label       used in container label
    // lvp_cbx
    // layout      layout information
    //     name
    //     data
    //     rc_type
    //     rc_nos
    //     flow
    // }}}
 	$name    = $a['name'];
	$label   = $a['label'];
	$help    = cst_def($a, '', 'help');
    $layout  = cst_def($a, array(), 'layout');
    $default = $a['default'];
    $extra = cst_def($a, '', 'extra');
    // {{{ Construct checkbox controls
    //      $lvp_cbx[$label]  = string
    //      $lvp_cbx[$label]['active'] = bool
    // }}}
    $lvp_cbx = $a['lvp_cbx'];
    $checkbox_ctrls = array();
    $values = frm_val(array(
        'name'      =>  $name,
        'default'   =>  $default,
        ));
    // $values needs to be an array for in_array below
    if ($values=='') { $values = array(); }
    foreach ($lvp_cbx as $k_cbx_label => $v_cbx_value) {
        $checked = '';
        if (in_array($v_cbx_value, $values)) { $checked = 'checked'; }
        // done: check name[] array vs scalar (a potential problem in multi-edit)
        // this is no longer a problem because frm_val now ensures correcteness of returning default post
        $checkbox_ctrls[] = "<input type=\"checkbox\" name=\"{$name}[]\" value=\"$v_cbx_value\" $checked $extra /> $k_cbx_label";
    }
    if (!isset($layout['name'])) $layout['name'] = '';
    if (!isset($layout['rc_nos'])) $layout['rc_nos'] = '';
    if (!isset($layout['flow'])) $layout['flow'] = '';
    $checkbox_ctrls = lyt_rc(array(
           'name'    => $layout['name'],
           'data'    => $checkbox_ctrls,
           'rc_type' => $layout['rc_type'],
           'rc_nos'  => $layout['rc_nos'],
           'flow'    => $layout['flow'],
        ));

    // validation
    if (!isset($a['fvalid']['name'])) $a['fvalid']['name']='';
    if (!isset($a['fvalid']['emsg'])) $a['fvalid']['emsg']='';
    $vresult = frm_vad(array(
        'fn_valid'  =>  $a['fvalid']['name'],
        'fn_emsg'   =>  $a['fvalid']['emsg'],
        'value'     =>  $values,
        ));
    $r['bvalid'] = $vresult['bvalid'];
    //$r['bvalid'] = true;

    $r['output'] = frm_out(array(
        'name'  =>  $name,
        'label' =>  $label,
        'ctrl'  =>  "$checkbox_ctrls",
        'msg'   =>  $vresult['emsg'],
        'help'  =>  $help,
        ));

    return $r;
}/*}}}*/
function frm_fie($a=array()) {/*{{{*/
    $a = cst_arr($a);
    $name    = cst_def($a, '', 'name');
    $label = cst_lbl(array('name'=> $name, 'label' => $a['label']));
	$help    = cst_def($a, '', 'help');

    $r['bvalid'] = true;

    $r['output'] = frm_out(array(
        'name'  =>  $name,
        'label' =>  $label,
        'ctrl'  =>  "<input type=\"file\" name=\"$name\" />",
        'msg'   =>  '',
        'help'  =>  $help,
    ));

    return $r;
}/*}}}*/
function frm_hid($a) {/*{{{*/
    $name    = cst_def($a, '', 'name');
    $default = cst_def($a, '', 'default');

    $value = frm_val(array(
        'name'      =>  $name,
        'default'   =>  $default,
    ));

    $r['bvalid'] = true;
    $r['output'] = "<input type=\"hidden\" name=\"$name\" value=\"$value\" />\n";
    return $r;
}/*}}}*/
function frm_hid2($a) {/*{{{*/
    $name 	= cst_def($a, '', 'name');
    $value 	= cst_def($a, '', 'value');

    $r['bvalid'] = true;
    $r['output'] = "<input type=\"hidden\" name=\"$name\" value=\"$value\" />\n";
    return $r;
}/*}}}*/
function frm_lbl($a) {/*{{{*/
    $a = cst_arr($a, 'label');
    $name    = cst_def($a, '', 'name');
    $label = $a['label'];
    $r['bvalid'] = true;
    $r['output'] = "<div id=\"$name\">$label</div>";
    return $r;
}/*}}}*/
function frm_lbd($a) {/*{{{*/
    // form label dual (label, default/value)
    $a = cst_arr($a, 'label');
    $name    = cst_def($a, '', 'name');
    $label   = cst_def($a, '', 'label');
    // frm_val is not required (or possible) because there is no form control, hence no post data
    //$default = frm_val(array('name', $name, 'default'=>$a['default']));
    $default    = cst_def($a, '', 'default');
    // extra default style
    $extra_ds   = cst_def($a, '', 'extra_ds');
    $r['bvalid'] = true;
    $r['output'] = frm_out(array(
        'name'  =>  $name,
        'label' =>  $label,
        'ctrl'  =>  "<div{$extra_ds}>$default</div>",
        'msg'   =>  '',
        'help'  =>  $a['help'],
    ));
    return $r;
}/*}}}*/
function frm_btn($a) {/*{{{*/
    $a = cst_arr($a, 'name');
    $name    = cst_def($a, '', 'name');
    $label   = cst_def($a, '', 'label');
    $extra = cst_def($a, '', 'extra');
    $r['bvalid'] = true;
    $r['output'] = frm_out(array(
        'name'  =>  $name,
        'label' =>  '&nbsp;',
        'ctrl'  =>  "<input type=\"button\" name=\"$name\" value=\"$label\" $extra />",
        'msg'   =>  '&nbsp;',
        'help'  =>  '&nbsp;',
        ));
    return $r;
}/*}}}*/
function frm_btr($a) {/*{{{*/
	// following reset button copy pasted from frm_bts with modification type=reset of course
	$a = cst_arr($a);
    $name   = cst_def($a, '', 'name');
    $label  = cst_lbl(array('name'=>$name, 'label'=>$a['label']));

    // validation
/*    $vresult = frm_vad(array(
        'fn_valid'  =>  $a['fvalid']['name'],
        'fn_emsg'   =>  $a['fvalid']['emsg'],
        'value'     =>  $value,
        ));
    $r['bvalid'] = $vresult['bvalid'];*/
    $r['bvalid'] = true;

    $output = frm_out(array(
        // No need for separate $label,$msg,$help columns because buttons don't require them
        'name'  =>  $name,
        'label' =>  '&nbsp;',
        'ctrl'  =>  "<input type=\"reset\" name=\"$name\" value=\"$label\" />",
        'msg'   =>  '&nbsp;',
        'help'  =>  '&nbsp;',
    ));
    $r['output'] = $output;

    return $r;
}/*}}}*/
function frm_bts($a) {/*{{{*/
    $a = cst_arr($a);
    $name   = $a['name'];
    $label  = cst_lbl(array('name'=>$name, 'label'=>$a['label']));

    // validation
    // dont think button submit has validation !!!
    /*
    $vresult = frm_vad(array(
        'fn_valid'  =>  $a['fvalid']['name'],
        'fn_emsg'   =>  $a['fvalid']['emsg'],
        'value'     =>  $value,
        ));
    $r['bvalid'] = $vresult['bvalid']; */
    $r['bvalid'] = true;

    $output = frm_out(array(
        // No need for separate $label,$msg,$help columns because buttons don't require them
        'name'  =>  $name,
        'label' =>  '&nbsp;',
        'ctrl'  =>  "<input type=\"submit\" name=\"$name\" id=\"$name\" value=\"$label\" />",
        'msg'   =>  '&nbsp;',
        'help'  =>  '&nbsp;',
    ));
    $r['output'] = $output;

    return $r;
}/*}}}*/
function frm_out($a) {/*{{{*/
    $o = '';
    $name  = $a['name'];
    $label = $a['label'];
    $ctrl  = $a['ctrl'];
    $msg   = $a['msg'];
    $help    = cst_def($a, '', 'help');

    // div wrapped in container is a molecule
    $o  = "\n<div id=\"{$name}_mol\">\n";
    $o .= "  <div class=\"flabel\">$label</div>\n";
    $o .= "  <div class=\"fctrl\">$ctrl</div>\n";
    $o .= "  <div class=\"fhelp\">$help</div>\n";
    $o .= "  <div class=\"femsg\">$msg</div>\n";
    $o .= "</div>\n";

    return $o;
}/*}}}*/
function frm_rad($a=array()) {/*{{{*/
    // name        used in container div id, actual checkbox name/id will come from lvp_cbx
    // label       used in container label
    // lvp_rad
    // layout      layout information
    //     name
    //     data
    //     rc_type
    //     rc_nos
    //     flow
 	$name    = $a['name'];
	$label   = $a['label'];
	$help    = cst_def($a, '', 'help');
    $layout  = $a['layout'];
    $default = $a['default'];
    $extra = cst_def($a, '', 'extra');
    // Construct radio controls
    //      $lvp_rad[$label]  = string
    //      $lvp_rad[$label]['active'] = bool
    $lvp_rad = $a['lvp_rad'];
    $radio_ctrls = array();
    $values = frm_val(array(
        'name'      =>  $name,
        'default'   =>  $default,
        ));
    // $values needs to be an array for in_array below
    if ($values=='') { $values = array(); }
    foreach ($lvp_rad as $k_rad_label => $v_rad_value) {
        $checked = '';
        if (in_array($v_rad_value, $values)) { $checked = 'checked'; }
        // done: check name[] array vs scalar (a potential problem in multi-edit)
        // this is no longer a problem because frm_val now ensures correcteness of returning default post

        // improve radio list with <label></label>
        //$radio_ctrls[] = "<input type=\"radio\" name=\"{$name}[]\" value=\"$v_rad_value\" $checked $extra /> $k_rad_label";
        $radio_ctrls[] =<<<EOT
        	<input type="radio" name="{$name}[]" value="$v_rad_value" id="{$name}_{$v_rad_value}" $checked $extra />
        	<label for="{$name}_{$v_rad_value}">$k_rad_label</label>
EOT;
    }
    $radio_ctrls = lyt_rc(array(
           'name'    => $layout['name'],
           'data'    => $radio_ctrls,
           'rc_type' => 'cols',
           'rc_nos'  => $layout['rc_nos'],
           'flow'    => $layout['flow'],
        ));

	// VALIDATION
    //$r['bvalid'] = true;
    // ??? for radio do we need multiple validation ???
    // validation: single validation or multiple validation -- $a['fvalid'] or $a['fvalid_array']
    // $vresult['bvalid'], $vresult['emsg']
    /*if ($a['fvalid_array']) {
        foreach ($a['fvalid_array'] as $fvalid) {
            $vresult = frm_vad(array(
                'fn_valid'  =>  $fvalid['name'],
                // note: emsg is redundant - u get back exactly what u put in - or empty if bvalid is true
                'fn_emsg'   =>  $fvalid['emsg'],
                'value'     =>  $value,
                ));
            $r['bvalid'] = $vresult['bvalid'];
            if ($r['bvalid']==false) break;
        }
    } else {*/
        $vresult = frm_vad(array(
            'fn_valid'  =>  $a['fvalid']['name'],
            // note: emsg is redundant - u get back exactly what u put in - or empty if bvalid is true
            'fn_emsg'   =>  $a['fvalid']['emsg'],
            'value'     =>  $values,
            ));
        $r['bvalid'] = $vresult['bvalid'];
    /*}*/

    $r['output'] = frm_out(array(
        'name'  =>  $name,
        'label' =>  $label,
        'ctrl'  =>  "$radio_ctrls",
        'msg'   =>  $vresult['emsg'],
        'help'  =>  $help,
        ));

    return $r;
}/*}}}*/
function frm_rca($a) {/*{{{*/
    // show recaptcha element
    $publickey = RCA_PUB_KEY;
    $a = cst_arr($a, 'name');
    $name = cst_def($a, 'recaptcha', 'name');
    $label = cst_def($a, '&nbsp;', 'label');
    $help    = cst_def($a, '', 'help');

    // $vresult has $vresult['bvalid'] and $vresult['emsg']
    $vresult = frm_vad(array(
        'fn_valid'  =>  'vld_rca',
        'fn_emsg'   =>  'The reCAPTCHA wasn\'t entered correctly. Please try again.',
        ));

    // $r has $r['bvalid'] and $r['output']
    /*
    $r = frm_lbl(array(
            'name'      =>  'recaptcha',
            'label'     =>  recaptcha_get_html($publickey)."<div class='recaptcha_error'>$vresult[emsg]</div>",
        ));
     */

    $rca_ctrl = <<<EOT
<script>
var RecaptchaOptions = {
   theme: 'custom',
   lang: 'en',
   custom_theme_widget: 'recaptcha_widget'
};
</script>

<div id="recaptcha_widget" style="display:none">
    <div id="recaptcha_image"></div>

    <span class="recaptcha_only_if_image">Enter the words above:</span>
    <span class="recaptcha_only_if_audio">Enter the numbers you hear:</span>

    <input type="text" id="recaptcha_response_field" name="recaptcha_response_field" />

    <div class="recaptcha_get_another"><a href="javascript:Recaptcha.reload()">Get another CAPTCHA</a></div>
    <div class="recaptcha_only_if_image"><a href="javascript:Recaptcha.switch_type('audio')">Get an audio CAPTCHA</a></div>
    <div class="recaptcha_only_if_audio"><a href="javascript:Recaptcha.switch_type('image')">Get an image CAPTCHA</a></div>
</div>

<script type="text/javascript" src="http://api.recaptcha.net/challenge?k=$publickey"></script>

EOT;

    $r['bvalid'] = $vresult['bvalid'];
    $r['output'] = frm_out(array(
        'name'  =>  $name,
        'label' =>  $label,
        //'ctrl'  =>  recaptcha_get_html($publickey),
        'ctrl'  =>  $rca_ctrl,
        'msg'   =>  $vresult['emsg'],
        //'help'  =>  $help,
        'help'  =>  '<div class="recaptcha_help"><a href="javascript:Recaptcha.showhelp()">[?]</a></div>',
        ));

    return $r;
}/*}}}*/
function err_404() {/*{{{*/
	header("HTTP/1.0 404 Not Found");
	exit();
}/*}}}*/
function fur_html($a) {/*{{{*/
	/*
	will set required sessions
	wil return filter values (for use later in data distillation)
	will return a frm_dsp
	array(
		'fur_frm_name'	=>	'',
		'
		)
	 */
	$fur_frm_name = cst_def($a, 'fur_frm_name_must_exist', 'fur_frm_name');



}/*}}}*/
function frm_ais($a) { /*{{{*/
}/*}}}*/
function frm_sis($a) { /*{{{*/
}/*}}}*/
function htm_srt($a=array()) {/*{{{*/
	// html for sort: up and down arrows
	$out = "<a href='' class='sort_asc sort_selected'>&uarr;</a><a href='' class='sort_dsc'>&darr;</a>";
	return $out;
}/*}}}*/
function htm_tbl_hdr_srt() {/*{{{*/
	// html table header with sort links

}/*}}}*/
function htm_res($a) {/*{{{*/
	// htm_res = html result (similar to result) there is always a count
	// Provides eg.	Results: 23 record(s)
	//				The following record(s) (is/are) about to be deleted
	$a = cst_arr($a, 'nos');
	$result_nos = cst_def($a, 0, 'nos');
	$singular	= cst_def($a, 'record', 'sng');
	$plural		= cst_def($a, 'records', 'plr');
	$text		= cst_def($a, 'Result: %s', 'txt');

	if ($result_nos > 1) {
		$sng_or_plr = $plural;
	} else {
		$sng_or_plr = $singular;
	}

	$sub_txt = '<span class="result_count">'.$result_nos.'</span> '.$sng_or_plr;
	$text = preg_replace('/%s/', "$sub_txt", $text);
	return '<div class="result">'.$text.'</div>';
}/*}}}*/
function im_type2ext($type) {/*{{{*/
	// internet media type 2 extension (mime->im)
	$app_type = array(
		'application/ogg'			=>	'ogg',
		'application/pdf'			=>	'pdf',
		'application/vnd.ms-excel'	=>	'xls',
		'application/vnd.ms-powerpoint'	=>	'ppt',
		'application/msword'		=>	'doc',
		'application/zip'			=>	'zip',
		'image/gif'					=>	'gif',
		'image/jpeg'				=>	'jpg',
		'image/png'					=>	'png',
		'image/tiff'				=>	'tif',
		'image/vnd.microsoft.icon'	=>	'ico',
		'text/html'					=>	'html',
		'text/javascript'			=>	'js',
		'text/plain'				=>	'txt',
		);
	return $app_type["$type"];
}/*}}}*/
function jas_cal_trg($a=array()) {/*{{{*/
    // name     name and id of the form control
    $name = $a['name'];
    $jas_cal_trg = "";
    if (APP_NAM == 'jos') {
        $jas_cal_trg = "onclick=\"return showCalendar('$name', '%Y-%m-%d');\"";
    }
    return $jas_cal_trg;
}/*}}}*/
function jas_frm_cls() {/*{{{*/
	// javascript form clear
    $out = "
    	<script type='text/javascript'>
    		function jas_frm_cls(arr) {
    			// first array element is form name, the rest are form elements
    			filter_form = arr.shift();
    			for (i in arr) {
    				ctrl_obj = document.getElementById(arr[i]);
    				if (ctrl_obj.type == 'text') {
						ctrl_obj.value = '';
					} else if (ctrl_obj.type == 'checkbox') {
						cbx_ctrl_obj = document.forms[filter_form].elements[arr[i]];
						for (j=0; j<cbx_ctrl_obj.length; j++) {
							cbx_ctrl_obj[j].checked = false;
						}
					}
    			}
    		}
    	</script>
    ";
	return $out;
}/*}}}*/
function jas_tgl($a) {/*{{{*/
	// javascript toggle
	// make sure you insert this javascript after the div areas in html are declared

	$jas_toggle_name =	$a['jas_toggle_name'];
    $div_area		 =	$a['div_area'];
    $div_ctrl		 =	$a['div_ctrl'];
    $html_hide		 =	$a['html_hide'];
    $html_unhide	 =	$a['html_unhide'];
    $cookie_name	 =	$a['cookie_name'];
    $def_display	 =	cst_def($a, '', 'def_status');
	$app_uri		 =	APP_URI;

    if (isset($_COOKIE[$cookie_name])) {
    	$display = $_COOKIE[$cookie_name];
    } else {
    	$display = $def_status;
    	setcookie($cookie_name, $display, time() + (60*60*24*365), APP_URI.'/');
    }

    if ($display) {
    	$ctrl_write = $html_unhide;
    } else {
    	$ctrl_write = $html_hide;
    }

	$out = "
		<script type='text/javascript'>
			var filter_div_area = document.getElementById('$div_area');
			var filter_div_ctrl = document.getElementById('$div_ctrl');
			filter_div_area.style.display = '$display';
			filter_div_ctrl.innerHTML = '$ctrl_write';
			function $jas_toggle_name() {
				dt = new Date();
				dt.setDate(dt.getDate()+365);
				dt = dt.toUTCString();
				if (filter_div_area.style.display=='none') {
					filter_div_area.style.display = '';
					filter_div_ctrl.innerHTML = '$html_hide';
					document.cookie = '$cookie_name=; expires='+dt+'; path=$app_uri/';
				} else {
					filter_div_area.style.display = 'none';
					filter_div_ctrl.innerHTML = '$html_unhide';
					document.cookie = '$cookie_name=none; expires='+dt+'; path=$app_uri/';
				}
			}
		</script>
	";
	return $out;
}/*}}}*/
function jas_tgl_jq($a) {/*{{{*/
	// javascript toggle jquery
	// make sure you insert this javascript after the div areas in html are declared

	$jas_toggle_name =	$a['jas_toggle_name'];
    $div_area		 =	$a['div_area'];
    $div_ctrl		 =	$a['div_ctrl'];
    $html_hide		 =	$a['html_hide'];
    $html_unhide	 =	$a['html_unhide'];
    $cookie_name	 =	$a['cookie_name'];
    $def_display	 =	cst_def($a, '', 'def_display');
    $app_uri		 =	APP_URI;

    if (isset($_COOKIE[$cookie_name])) {
    	$display = $_COOKIE[$cookie_name];
    } else {
    	// looks like a mistake $display = $def_status;
    	$display = $def_display;
    	setcookie($cookie_name, $display, time() + (60*60*24*365), APP_URI.'/');
    }

    if ($display=='none') {
    	$ctrl_write = $html_unhide;
    } else {
    	$ctrl_write = $html_hide;
    }

	$out = "
		<script type='text/javascript'>
			var va_adm_filter_div_area = document.getElementById('$div_area');
			var va_adm_filter_div_ctrl = document.getElementById('$div_ctrl');
			va_adm_filter_div_area.style.display = '$display';
			va_adm_filter_div_ctrl.innerHTML = '$ctrl_write';

		    $('#$div_ctrl').click(function() {
		        $('#$div_area').slideToggle('slow', function() {
		        	//alert(va_adm_filter_div_area.style.display);
			        dt = new Date();
					dt.setDate(dt.getDate()+365);
					dt = dt.toUTCString();

					if (va_adm_filter_div_area.style.display=='none') {
						va_adm_filter_div_ctrl.innerHTML = '$html_unhide';
						document.cookie = '$cookie_name=none; expires='+dt+'; path=$app_uri/';
					} else {
						va_adm_filter_div_ctrl.innerHTML = '$html_hide';
						document.cookie = '$cookie_name=; expires='+dt+'; path=$app_uri/';
					}
		        });
		    });

		</script>
	";
	return $out;
}/*}}}*/
function jas_htm_new2apply($a) {/*{{{*/
    $id     = $a['id'];
    $form   = $a['form'];
    $action = $a['action'];
    $out =<<<EOT
<form name="$form" method="post" action="$action">
<input type="hidden" name="id[]" value="$id" />
</form>
<script type='text/javascript'>
document.forms['$form'].submit();
</script>
EOT;
    return $out;
}/*}}}*/
function jas_popup_window_url($a) {/*{{{*/

	$popup_width  = $a['width'];
	$popup_height = $a['height'];
	$margin_top	  = cst_def($a, '30', 'margin_top');
	$close_txt	  = $a['close_txt'];

	if (APP_NAM=='jos') {
		if (JVERSION > 1.5) {
			$out_ajax =<<<EOT
				new Request({
			        url: url,
			        method: 'get',
					onSuccess: function(responseText){
					    document.getElementById('popup_form').innerHTML = responseText;
					}
			    }).send();
EOT;
		} else {
			$out_ajax =<<<EOT
				new Ajax(url, {
					method: 'get',
					update: $('popup_form')
				}).request({noCache : true});
EOT;
		}
	}

	$out =<<<EOT
<script type="text/javascript">

//function va_popup_create_url(url) {
function va_popup_create_url(url, argv_popw, argv_poph) {

	var popup_width = $popup_width;
	if (argv_popw) {
		popup_width = argv_popw;
	}

	var popup_height = $popup_height;
	if (argv_poph) {
		popup_height = argv_poph;
	}

	var body_height = document.body.scrollHeight;
 	var win_height = document.body.clientHeight;
	if (body_height < win_height) body_height = win_height;
	var win_width = document.body.clientWidth + 20;

	var popup_window_top = (va_scroll_top()+$margin_top) + 'px';

 	//window.scrollTo(0,0);

	var veil_bg = document.createElement('div');
	veil_bg.setAttribute('id', "veil_bg");
	veil_bg.style.display='inline';

	veil_bg.style.height = body_height + 'px';
	//veil_bg.style.height = "100%";
	veil_bg.style.width = "100%";
	veil_bg.style.backgroundColor = "black";
	veil_bg.style.filter = "alpha(opacity=30)";
	veil_bg.style.opacity = ".30";
	veil_bg.style.MozOpacity = ".30";
	veil_bg.style.position = "absolute";
	veil_bg.style.top = '0px';
	veil_bg.style.left = '0px';
	//veil_bg.style.zIndex = 1000;

	var popup_window = document.createElement('div');
	popup_window.setAttribute('id', 'popup_window');
	popup_window.style.display='inline';

	popup_window.style.position = "absolute";
	popup_window.style.top = popup_window_top;
	popup_window.style.width = popup_width + 'px';
	popup_window.style.height = popup_height + 'px';
	popup_window.style.left =  ((win_width - popup_width) / 2) + 'px';
	//popup_window.style.zIndex = 1001;
	popup_window.innerHTML = "<div id='popup_window_close'><a href='#' onclick='va_popup_close(); return false;'>$close_txt</a></div>";
	popup_window.innerHTML += '<div id="popup_form"></div>';

	document.body.appendChild(veil_bg);
	document.body.appendChild(popup_window);

	//new Ajax(url, {
	//	method: 'get',
	//	update: $('popup_form')
	//}).request({noCache : true});

	$out_ajax

	//document.documentElement.style.overflow = "auto";
	//document.body.scroll = "yes";

}
function va_popup_close() {

	//document.getElementById("popup_window").innerHTML = '';
	//document.getElementById("popup_window").style.display='none';
	//document.getElementById("veil_bg").innerHTML = '';
	//document.getElementById("veil_bg").style.display='none';

	var veil_bg = document.getElementById("veil_bg");
	document.body.removeChild(veil_bg);

	var popup_window = document.getElementById("popup_window");
	document.body.removeChild(popup_window);

	//document.documentElement.style.overflow = "auto";
	//document.body.scroll = "yes";
}
function va_scroll_top() {
	var scroll_top = document.body.scrollTop;
	if (scroll_top == 0) {
	    if (window.pageYOffset) {
			scroll_top = window.pageYOffset;
	    } else {
			scroll_top = (document.body.parentElement) ? document.body.parentElement.scrollTop : 0;
		}
	}
	return scroll_top;
}
</script>
EOT;
	return $out;
}/*}}}*/
function jos_cfg($a) {
	// jos = joomla
	// cfg = configuration
	$path_to_cfg_file = $a['cfg'];

	/*
	$ecv_conf_content = explode("\n", file_get_contents('../../e-cv/configuration.php'));

	foreach ($ecv_conf_content as $value) {
		if (preg_match('/\$user\b/', $value)) {
			$ecv_db_user_arr = explode('=', $value);
			$ecv_db['user'] = clean_name_value($ecv_db_user_arr[1]);
		}
		if (preg_match('/\$password\b/', $value)) {
			$ecv_db_password_arr = explode('=', $value);
			$ecv_db['password'] = clean_name_value($ecv_db_password_arr[1]);
		}
		if (preg_match('/\$db\b/', $value)) {
			$ecv_db_name_arr = explode('=', $value);
			$ecv_db['name'] = clean_name_value($ecv_db_name_arr[1]);
		}
	}
	*/
}
function jos_cfg_clean($a) {
	/*
	$value = trim($value);
	$value = preg_replace('/\'/', '', $value);
	$value = preg_replace('/;/', '', $value);
	//$value = preg_replace('', '', $value);
	return $value;
	*/
	if($a['value']) {

		return $value;
	}
}
function kfe_() {/*{{{*/
    $o_txt  = 'Sing a song of sixpence,';
    $o_txt .= 'A pocket full of rye.';
    $o_txt .= 'Four and twenty blackbirds,';
    $o_txt .= 'Baked in a pie.';
    return $o_txt;
}/*}}}*/
function lic() {/*{{{*/
	$host = $_SERVER["HTTP_HOST"];

	// Avoid www. prefix
	$host = preg_replace('/^www\./', '', $host);

	$addr = $_SERVER["SERVER_ADDR"];
	$port = $_SERVER["SERVER_PORT"];
	$text = $host.$addr.$port.APP_NAME;

	//$iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_CFB);
	//$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
	$iv = 'vappscom';
	$key = "Beat the dog and the lion will behave";

	$license = VA_LICENSE;
	if (!$license) {
		return false;
	}

	$license = base64_decode($license);
	$license_text = mcrypt_decrypt(MCRYPT_BLOWFISH, $key, $license, MCRYPT_MODE_CFB, $iv);

	if ($license_text == $text) {
		return true;
	} else {
		return false;
	}
}/*}}}*/
function lng_mrk($a) {/*{{{*/
    // lng = language, mrk = markup
    // Simplify markup by eliminating start and end tags, make markup readable, and more [..add new rules]

    // ? at beginning of sentence arg signifies beginning of help
    // ? Hello World        <a title="Hello World">[?]</a>
    $out = preg_replace('/\?\s(.*)/', '<a title="\1">[?]</a>', $a);

    return $out;
}/*}}}*/
function lvp_tbl($a) {/*{{{*/
    // Args
    $table = $a['table'];
    $col_label = $a['label'];
    $col_value = $a['value'];
    //$extra = cst_def($a['extra'], " order by `$col_label` ");
    $extra = cst_def($a, " order by `$col_label` ", 'extra');

    $sql = "select * from `$table` $extra ";
    $result = mysql_query($sql);
    $lvp = array();
    while ($row = mysql_fetch_assoc($result)) {
        $lvp[$row[$col_label]] = $row[$col_value];
    }
    return $lvp;
}/*}}}*/
function lvp_nar($a) {/*{{{*/
    $col_lv = $a;

    $lvp = array();
    foreach ($col_lv as $lv) {
        $lvp[$lv] = $lv;
    }
    return $lvp;
}/*}}}*/
function lyt_rc($a=array()) {/*{{{*/
    // {{{ Args
 	// name		used in <table id="$name">
	// data		data array
	// rc_type	    rows or cols
	// rc_nos	    rows or cols nos
	// flow		down, accross (default)
    // }}}
    // {{{ Example
    // $data=range(1,1100);
    // echo lyt_rc(array(
    //     'name'		=>	'lyt_rc',
    //     'data'		=>	$data,
    //     'rc_type'	=>	'cols',
    //     'rc_nos'	=>	'30',
    //     //'flow'		=>	'down',
    //     'flow'		=>	'accross',
    // ));
    // }}}
	$name = $a['name'];
	$data = $a['data'];
	
	$td_extra = $a['td_extra'];
	if ($td_extra) $td_extra = " ".$td_extra;

	$rc_type = 'cols';
	if (isset($a['rc_type']) && $a['rc_type']) { $rc_type = $a['rc_type']; }

    $rc_nos = 1;
	if (isset($a['rc_nos']) && $a['rc_nos']) { $rc_nos = $a['rc_nos']; }

	$flow = 'accross';
	if (isset($a['flow']) && $a['flow']) { $flow = $a['flow']; }

	$data_nos = count($data);
	// how many cols?
	// how many rows?
	if ($rc_type=='cols') {
		$cols = $rc_nos;
		$rows = ceil($data_nos/$cols);
	// $rc_type=='rows')
	} else {
		$rows = $rc_nos;
		$cols = ceil($data_nos/$rows);
	}
	// output
	$o_trtd = '';
	if ($flow=='accross') {
		for($r=1; $r<=$rows; $r++) {
			$o_trtd .= "<tr>\n";
			for($c=1; $c<=$cols; $c++) {
				$o_trtd .= "  <td{$td_extra}>";
				$o_trtd .= array_shift($data);
				$o_trtd .= "  </td>\n";
			}
			$o_trtd .= "</tr>\n";
		}
	// flow=='down'
	} else {
		$o_trtd .= "<tr>\n";
		for($c=1; $c<=$cols; $c++) {
			$o_trtd .= "<td valign=\"top\">\n";
			$o_trtd .= "<table id=\"inner\">\n";
			for($r=1; $r<=$rows; $r++) {
				$o_trtd .= "<tr>";
				$o_trtd .= "  <td{$td_extra}>";
				$o_trtd .= array_shift($data);
				$o_trtd .= "  </td>";
				$o_trtd .= "</tr>\n";
			}
			$o_trtd .= "</table>\n";
			$o_trtd .= "</td>\n";
		}
		$o_trtd .= "</tr>\n";
	}
	$o_html  = ''."\n";
	$o_html .= "<table id=\"$name\">\n";
	$o_html .= $o_trtd;
	$o_html .= "</table>\n";
	return $o_html;
}/*}}}*/
function mod_url_bit($mod_url_full) {/*{{{*/
	// mod url bits
	$mub = explode('/', MOD_URL_FULL);
	$mub = array_pad($mub, 5, '');
	return $mub;
}/*}}}*/
function name_human2system($name) {/*{{{*/
	$name = trim($name);
	$name = preg_replace('/\s/', '_', $name);
	$name = preg_replace('/\'/', '_', $name);
	$name = preg_replace('/\"/', '_', $name);
	$name = preg_replace('/:/', '_', $name);
	return $name;
}/*}}}*/
function not_() {/*{{{*/
    // {{{ Life of Data
    // Select -> Filter -> Format
    // There are various stages of each of the transformations above.
    // 1. It gets pulled from database, with some options, all, where id=?, order, etc
    // 2. Some data gets dropped depending on various user selected criteria, yes, data drop might be the biggest thing discovered after coke :)
    // 3. More data gets dropped depending on page number, and items per page
    // 4. Data members get formatted
    // 5. Data families get formatted depending on div sections
    // 6. Data families get formatted depending on odd even rows
    // }}}

}/*}}}*/
function pge_adm_tle($a) { /*{{{*/
    // page title
    //$title    = cst_def($a['title'], MOD_NAM);
    $title    = cst_def($a, MOD_NAM, 'title');
    $subtitle = $a['subtitle'];
    $image    = cst_def($a, 'generic.png', 'image');
    //if (isset($a['image']) && $a['image']) { $image = $a['image']; }

    $out = '';
    switch (APP_NAM) {
        case 'jos':
            if ($image != 'generic.png') {
                $class_name = substr($image, 0, -4);
                $style .= ".icon-48-$class_name { background-image: url(".MOD_ADM_URI_REP."/images/$image); }";
                $doc =& JFactory::getDocument();
                $doc->addStyleDeclaration($style);
            }
            JToolBarHelper::title(JText::_($title)." <small><small>$subtitle</small></small>", $image );
            return;
            break;
    }
    return $out;
}/*}}}*/
function pge_adm_tbr($a) {/*{{{*/
    $form  = cst_def($a, '', 'form');
    $button = cst_def($a, '', 'button');
    $edl_to = cst_def($a, '', 'edl_to');
    $option = cst_def($_GET, '', 'option');
    $out_js_ha = $out_js_el = $out_js_sb = $out_html_toolbar = '';
    $ha_exists = false;
    $el_exists = false;
    /*{{{*/ $out_js_ha = <<<EOT
// dynamically create hidden form field for apply
function add_frm_hid_apply() {
	if (!document.forms["$form"].apply) {
	    var frm_hid_apply = document.createElement("input");
	   	frm_hid_apply.setAttribute("type", "hidden");
	    frm_hid_apply.setAttribute("name", "apply");
	    frm_hid_apply.setAttribute("value", "0");
	    document.getElementById("$form").appendChild(frm_hid_apply);
    }
}
window.onload = add_frm_hid_apply;
EOT;
    /*}}}*/
    $out_js1 = '';
    switch (APP_NAM) {
        case 'jos':
            $out_js_el = "
	            function adm_edl_js(id, destination) {
	                //id = 'id_'+id;
	                //document.getElementById(id).checked = true;
	                //document.forms['$form'].action = '$edl_to';
	                //document.forms['$form'].submit();

	                id = 'id_'+id;
	                if (!destination) {
	                    destination_action = '$edl_to';
	                } else {
						destination_action = '".APP_URI."/administrator/'+destination;
					}
	                document.getElementById(id).checked = true;
	                document.forms['$form'].action = destination_action;
	                document.forms['$form'].submit();
	            }
				";

            foreach ($button as $ktask => $vbtn_to) {
                if ($ktask=='add') { /*{{{*/
                    JToolBarHelper::addNewX();
                    $out_js_sb .= <<<EOT
                        if (task=='add') {
                            document.forms['$form'].action = '$vbtn_to';
                            document.forms['$form'].submit();
                        }

EOT;
                }/*}}}*/
                elseif ($ktask=='edit') { /*{{{*/
                    JToolBarHelper::editListX();
                    $out_js_sb .= <<<EOT
                        if (task=='edit') {
                            elem_len = document.forms['$form'].elements.length;
                            any_checked = false;
                            for (i=0; i<elem_len; i++) {
                                if (document.forms['$form'].elements[i].checked==true) { any_checked = true; }
                            }
                            if (any_checked==false) {
                                alert("Please select a record to edit.");
                                return false;
                            }
                            document.forms['$form'].action = '$vbtn_to';
                            document.forms['$form'].submit();
                        }

EOT;
                }/*}}}*/
                elseif ($ktask=='remove') { /*{{{*/
                    JToolBarHelper::deleteList();
                    $out_js_sb .= <<<EOT
                        if (task=='remove') {
                            elem_len = document.forms['$form'].elements.length;
                            any_checked = false;
                            for (i=0; i<elem_len; i++) {
                                if (document.forms['$form'].elements[i].checked==true) { any_checked = true; }
                            }
                            if (any_checked==false) {
                                alert("Please select a record to delete.");
                                return false;
                            }
                            document.forms['$form'].action = '$vbtn_to';
                            document.forms['$form'].submit();
                        }

EOT;
                }/*}}}*/
                elseif ($ktask=='save') { /*{{{*/
                    JToolBarHelper::save();
                    $out_js_sb .= <<<EOT
                        if (task=='save') {
                            document.forms['$form'].apply.value = '0';
                            document.forms['$form'].action = '$vbtn_to';
                            document.forms['$form'].submit();
                        }

EOT;
                }/*}}}*/
                elseif ($ktask=='apply') { /*{{{*/
                    JToolBarHelper::apply();
                    $ha_exists = true;
                    $out_js_sb .= <<<EOT
                        if (task=='apply') {
                            document.forms['$form'].apply.value = '1';
                            document.forms['$form'].action = '$vbtn_to';
                            document.forms['$form'].submit();
                        }

EOT;
                }/*}}}*/
                elseif ($ktask=='confirm') { /*{{{*/
                    JToolBarHelper::apply('confirm', 'Confirm');
                    $out_js_sb .= <<<EOT
                        if (task=='confirm') {
                            document.forms['$form'].action = '$vbtn_to';
                            document.forms['$form'].submit();
                        }

EOT;
                }/*}}}*/
                elseif ($ktask=='cancel') { /*{{{*/
                    JToolBarHelper::cancel();
                    $out_js_sb .= <<<EOT
                        if (task=='cancel') {
                            //document.forms['$form'].action = '$vbtn_to';
                            //document.forms['$form'].submit();
                            window.location.href='$vbtn_to';
                        }

EOT;
                } /*}}}*/
                elseif ($ktask=='close') { /*{{{*/
                    JToolBarHelper::cancel('close', 'Close');
                    $out_js_sb .= <<<EOT
                        if (task=='close') {
                            //document.forms['$form'].action = '$vbtn_to';
                            //document.forms['$form'].submit();
                            window.location.href='$vbtn_to';
                        }

EOT;
                }/*}}}*/
                else {/*{{{*/
                    $id_required = false;
                    if (preg_match('/^!/', $ktask)) {
                        $id_required = true;
                        $ktask = preg_replace('/^!/', '', $ktask);
                    }
                    $button_title = preg_replace('/_/', ' ', $ktask);
                    $alert_title  = $button_title;
                    $button_title = ucwords($button_title);
                    $style = ".icon-32-$ktask { background-image: url(".MOD_ADM_URI_REP."/images/$ktask.png); }";
                    $doc =& JFactory::getDocument();
                    $doc->addStyleDeclaration($style);

                    JToolBarHelper::customX("$ktask", "$ktask.png", "$ktask.png", "$button_title");

                    $out_js_sb .= "if (task=='$ktask') { \n";
                    if ($id_required==true) {/*{{{*/
                        $out_js_sb .= <<<EOT
                                elem_len = document.forms['$form'].elements.length;
                                any_checked = false;
                                for (i=0; i<elem_len; i++) {
                                    if (document.forms['$form'].elements[i].checked==true) { any_checked = true; }
                                }
                                if (any_checked==false) {
                                    alert("Please select a record to $alert_title.");
                                    return false;
                                }

EOT;
                    }/*}}}*/
                    $out_js_sb .= <<<EOT
                            //document.forms['$form'].action = 'index.php?option=$option&d=$vbtn_to';
                            document.forms['$form'].action = '$vbtn_to';
                            document.forms['$form'].submit();

EOT;

                    $out_js_sb .= "} \n";

                }/*}}}*/
            }
            $out_js      = "\n<script type='text/javascript'>\n";
            if ($edl_to)    { $out_js .= $out_js_el; }
            if ($ha_exists) { $out_js .= $out_js_ha; }
            $out_js     .= $out_js_el;

            if (JVERSION > 1.5) {
            	$out_js     .= "Joomla.submitbutton = function (task) {\n $out_js_sb \n}\n";
            } else {
            	$out_js     .= "function submitbutton(task) {\n $out_js_sb \n}\n";
            }
            $out_js     .= "</script>\n";
            $out_js     .= "<form name=\"adminForm\"><input name=\"boxchecked\" type=\"hidden\" value=\"true\"></form>\n\n";
            break;
        case 'va':
        	// TODO destination should be completely controlled via the passed param:
        	// CHANGE document.forms['$form'].action = '".APP_URI."/admin/'+destination;
        	// TO document.forms['$form'].action = destination;
        	$out_html_toolbar .= "<table><tr>";
            /*{{{*/ $out_js_el = "
                function adm_edl_js(id, destination) {
                    // admin edit list
                    // backward compat, destination may not be defined
                    id = 'id_'+id;
                    if (!destination) {
                        destination = '$edl_to';
                    }
                    //alert(id + '-' + destination);
                    document.getElementById(id).checked = true;
                    //document.forms['$form'].action = '".APP_URI."/admin/$edl_to';
                    document.forms['$form'].action = '".APP_URI."/admin/'+destination;
                    document.forms['$form'].submit();
                }
                ";
            /*}}}*/
            foreach ($button as $ktask => $vbtn_to) {
                if ($ktask=='add') { /*{{{*/
                    //JToolBarHelper::addNewX();
                    $out_html_toolbar .=
                    	"<td class='admin_toolbar_create' id='admin_toolbar_button' title='Add' onclick=\"submitbutton('add');\"><span>Create</span></td>";
                    $out_js_sb .= "
                        if (task=='add') {
                            document.forms['$form'].action = '".APP_URI."/admin/$vbtn_to';
                            document.forms['$form'].submit();
                        }
                        ";
                }/*}}}*/
                elseif ($ktask=='edit') { /*{{{*/
                    //JToolBarHelper::editListX();
                    $out_html_toolbar .=
                    	//"<a href='#' onclick=\"submitbutton('edit');\" class='admin_toolbar'>Edit</a>";
                    	"<td class='admin_toolbar_edit' id='admin_toolbar_button' title='Edit' onclick=\"submitbutton('edit');\"><span>Edit</span></td>";
                    $out_js_sb .= "
                        if (task=='edit') {
                            elem_len = document.forms['$form'].elements.length;
                            any_checked = false;
                            for (i=0; i<elem_len; i++) {
                                if (document.forms['$form'].elements[i].checked==true) { any_checked = true; }
                            }
                            if (any_checked==false) {
                                alert('Please select a record to edit.');
                                return false;
                            }
                            document.forms['$form'].action = '".APP_URI."/admin/$vbtn_to';
                            document.forms['$form'].submit();
                        }
                        ";
                }/*}}}*/
                elseif ($ktask=='remove') { /*{{{*/
                    //JToolBarHelper::deleteList();
                    $out_html_toolbar .=
                    	//"<a href='#' onclick=\"submitbutton('remove');\" class='admin_toolbar'>Delete</a>";
                    	"<td class='admin_toolbar_remove' id='admin_toolbar_button' title='Delete' onclick=\"submitbutton('remove');\"><span>Delete</span></td>";
                    $out_js_sb .= "
                        if (task=='remove') {
                            elem_len = document.forms['$form'].elements.length;
                            any_checked = false;
                            for (i=0; i<elem_len; i++) {
                                if (document.forms['$form'].elements[i].checked==true) { any_checked = true; }
                            }
                            if (any_checked==false) {
                                alert('Please select a record to delete.');
                                return false;
                            }
                            document.forms['$form'].action = '".APP_URI."/admin/$vbtn_to';
                            document.forms['$form'].submit();
                        }
                        ";
                }/*}}}*/
                elseif ($ktask=='save') { /*{{{*/
                    //JToolBarHelper::save();
                    $out_html_toolbar .=
                    	//"<a href='#' onclick=\"submitbutton('save');\" class='admin_toolbar'>Save</a>";
                    	"<td class='admin_toolbar_save' id='admin_toolbar_button' title='Save' onclick=\"submitbutton('save');\"><span>Save</span></td>";
                    $out_js_sb .= "
                        if (task=='save') {
                            document.forms['$form'].apply.value = '0';
                            document.forms['$form'].action = '".APP_URI."/admin/$vbtn_to';
                            document.forms['$form'].submit();
                        }
                        ";
                }/*}}}*/
                elseif ($ktask=='apply') { /*{{{*/
                    //JToolBarHelper::apply();
                    $out_html_toolbar .=
                    	//"<a href='#' onclick=\"submitbutton('apply');\" class='admin_toolbar'>Apply</a>";
                    	"<td class='admin_toolbar_apply' id='admin_toolbar_button' title='Apply' onclick=\"submitbutton('apply');\"><span>Apply</span></td>";
                    $ha_exists = true;
                    $out_js_sb .= "
                        if (task=='apply') {
                            document.forms['$form'].apply.value = '1';
                            document.forms['$form'].action = '".APP_URI."/admin/$vbtn_to';
                            document.forms['$form'].submit();
                        }
                        ";
                }/*}}}*/
                elseif ($ktask=='confirm') { /*{{{*/
                    //JToolBarHelper::apply('confirm', 'Confirm');
                    $out_html_toolbar .=
                    	//"<a href='#' onclick=\"submitbutton('confirm');\" class='admin_toolbar'>Confirm</a>";
                    	"<td class='admin_toolbar_confirm' id='admin_toolbar_button' title='Confirm' onclick=\"submitbutton('confirm');\"><span>Confirm</span></td>";
                    $out_js_sb .= "
                        if (task=='confirm') {
                            document.forms['$form'].action = '".APP_URI."/admin/$vbtn_to';
                            document.forms['$form'].submit();
                        }
                        ";
                }/*}}}*/
                elseif ($ktask=='cancel') { /*{{{*/
                    //JToolBarHelper::cancel();
                    $out_html_toolbar .=
                    	//"<a href='#' onclick=\"submitbutton('cancel');\" class='admin_toolbar'>Cancel</a>";
                    	"<td class='admin_toolbar_cancel' id='admin_toolbar_button' title='Cancel' onclick=\"submitbutton('cancel');\"><span>Cancel</span></td>";
                    $out_js_sb .= "
                        if (task=='cancel') {
                            document.forms['$form'].action = '".APP_URI."/admin/$vbtn_to';
                            document.forms['$form'].submit();
                        }
                        ";
                } /*}}}*/
                elseif ($ktask=='close') { /*{{{*/
                    //JToolBarHelper::cancel('close', 'Close');
                    $out_html_toolbar .=
                    	//"<a href='#' onclick=\"submitbutton('close');\" class='admin_toolbar'>Close</a>";
                    	"<td class='admin_toolbar_close' id='admin_toolbar_button' title='Close' onclick=\"submitbutton('close');\"><span>Close</span></td>";
                    $out_js_sb .= "
                        if (task=='close') {
                            document.forms['$form'].action = '".APP_URI."/admin/$vbtn_to';
                            document.forms['$form'].submit();
                        }
                        ";
                }/*}}}*/
                else {/*{{{*/
                    $id_required = false;
                    if (preg_match('/^!/', $ktask)) {
                        $id_required = true;
                        $ktask = preg_replace('/^!/', '', $ktask);
                    }
                    $button_title = preg_replace('/_/', ' ', $ktask);
                    $alert_title  = $button_title;
                    $button_title = ucwords($button_title);
                    //$style = ".icon-32-$ktask { background-image: url(".MOD_ADM_URI_REP."/images/$ktask.png); }";
                    //$doc =& JFactory::getDocument();
                    //$doc->addStyleDeclaration($style);

                    //JToolBarHelper::customX("$ktask", "$ktask.png", "$ktask.png", "$button_title");
                    $out_html_toolbar .=
                    	//"<a href='#' onclick=\"submitbutton('$ktask');\" class='admin_toolbar'>$button_title</a>";
                    	"<td class='admin_toolbar_$ktask' id='admin_toolbar_button' title='$button_title' onclick=\"submitbutton('$ktask');\"><span>$button_title</span></td>";

                    $out_js_sb .= "if (task=='$ktask') { \n";
                    if ($id_required==true) {/*{{{*/
                        $out_js_sb .= "
                                elem_len = document.forms['$form'].elements.length;
                                any_checked = false;
                                for (i=0; i<elem_len; i++) {
                                    if (document.forms['$form'].elements[i].checked==true) { any_checked = true; }
                                }
                                if (any_checked==false) {
                                    alert('Please select a record to $alert_title.');
                                    return false;
                                }

                                ";
                    }/*}}}*/
                    $out_js_sb .= "
                            document.forms['$form'].action = '".APP_URI."/admin/$vbtn_to';
                            document.forms['$form'].submit();
                            ";

                    $out_js_sb .= "} \n";

                }/*}}}*/
            }
            $out_html_toolbar .= "</tr></table>";
            $out_js      = "\n<script type='text/javascript'>\n";
            if ($edl_to)    { $out_js .= $out_js_el; }
            if ($ha_exists) { $out_js .= $out_js_ha; }
            $out_js     .= $out_js_el;
            $out_js     .= "function submitbutton(task) {\n $out_js_sb \n}\n";
            $out_js     .= "</script>\n";
            $out_js     .= "<form name=\"adminForm\"><input name=\"boxchecked\" type=\"hidden\" value=\"true\"></form>\n\n";
            break;
    }
    return $out_js."\n\n".$out_html_toolbar."\n\n";
}/*}}}*/
function pgn_htm($a) {/*{{{*/
	// pagina html formatter

}/*}}}*/
function pst_() {/*{{{*/
    // the value from $_POST['var'] can be either scalar or array (checkbox and select)
    // the value from $_FILES['file'] is often blob (hypothesis needs further testing)
}/*}}}*/
function pst_var() {/*{{{*/

}/*}}}*/
function rc0() {/*{{{*/
    // APP init
    // define('', '');
    // include various js, styles

    // Application guessing/*{{{*/
    // joomla
    if (class_exists('JConfig')) {
        // Defines
        $jcf = new JConfig();
        define('APP_NAM', 'jos');
        define('APP_DBH', $jcf->host);
        define('APP_DBN', $jcf->db);
        define('APP_DBU', $jcf->user);
        define('APP_DBP', $jcf->password);

        $app_uri = preg_replace('/\/$/', '', JURI::base());
        if (preg_match('/administrator/', $app_uri)) {
        	define('APP_URI', preg_replace('/\/administrator/', '', $app_uri));
        	define('APP_ADMIN_URI', $app_uri);
        } else {
        	define('APP_URI', $app_uri);
        }

        // front or admin end
        // include, exclude stuff based on front or admin

        // Calendar (front-end)
        if (!preg_match('/administrator/', APP_URI)) {
            echo '<script type="text/javascript" src="'.APP_URI.'/includes/js/joomla.javascript.js"></script>';
        }
        JHTML::_('behavior.calendar');
        // Other JS function translators

        // style
        // Never echo directly
        echo sty_gen();


        define('TBL_ADM_LST_STY', 'class="adminlist"');
    // wordpress, weak assumption DB_HOST
    } elseif (defined('DB_HOST')) {
        define('APP_NAM', 'wps');
        define('APP_DBH', DB_HOST);
        define('APP_DBN', DB_NAME);
        define('APP_DBU', DB_USER);
        define('APP_DBP', DB_PASSWORD);

        wps_tpl();

    // drupal
    } elseif (defined('DPL')) {
        define('APP_NAM', 'dpl');
    // origami
    } else {
        define('APP_NAM', 'va');
        // VA apps are expected to have config.php in the same directory as "kafe.php=functions.php"
        include('config.php');

		if (defined('VA_TIMEZONE')) {
	        date_default_timezone_set(VA_TIMEZONE);
		} else {
	        date_default_timezone_set('UTC');
		}

        define('TBL_ADM_LST_STY', 'id="admin_list"');
    }/*}}}*/

    // {{{ The sharp distinction about ori and others (jos, wps, dpl)
    // is the fact that ori is an app of its own and would require
    // init of various elements (permission, templates, etc) before
    // js or styles can be directly echoed. In other apps we don't
    // think twice before echoing js, styles because we take for
    // granted that the html or output mechanisms have all ready
    // been ensured by the application. }}}

    // database
    $link = mysql_connect(APP_DBH, APP_DBU, APP_DBP);
    if (!$link) { die('Could not connect: ' . mysql_error()); }
    mysql_set_charset('utf8', $link);
    mysql_select_db(APP_DBN);

    // POST_SELF
    // just post isn't enough as page may be posted from another source
    $post_self = '';
    if ($_POST) {
        // check for POST from within same page - an actual form submission for edit new del
        // or POST from another page - like select list of records
        $prequest_uri = preg_quote($_SERVER['REQUEST_URI'], '/');
        if (preg_match("/$prequest_uri/i", $_SERVER['HTTP_REFERER'])) {
            $post_self = 'TRUE';
        }
    }
    define('POST_SELF', $post_self);

}/*}}}*/
function rc1() {/*{{{*/
}/*}}}*/
function rnd_key($a=array()) {/*{{{*/

	$key_type 	= cst_def($a, 'url', 'key_type');
	$key_len 	= cst_def($a, 8, 'key_len');
	$repeat		= cst_def($a, 'false', 'repeat');

    // chr_pool
    // key_length
    // key_type     url, password, alphabets
    $achr_pool['url'] = '0123456789abcdefghijklmnopqrstuvwxyz';
    $achr_pool['pwd'] = '!@#$%&_=+[]{};:,.?01234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    $achr_pool['alpha'] = 'abcdefghijklmnopqrstuvwxyz';
    $achr_pool['num'] = '0123456789';

    $chr_pool 	= cst_def($a, $achr_pool[$key_type], 'chr_pool');

    //$key_type = 'url';
    //if (isset($a['key_type']) && $a['key_type']) { $key_type = $a['key_type']; }

    //$chr_pool = $achr_pool[$key_type];
    //if (isset($a['chr_pool']) && $a['chr_pool']) { $chr_pool = $a['chr_pool']; }

    //$key_len = 8;
    //if (isset($a['key_len']) && $a['key_len']) { $key_len = $a['key_len']; }

    $lenm1_key = $key_len - 1;
    $lenm1_chr_pool = strlen($chr_pool) - 1;

    $key = '';
    $atmp_mtr = array();
    $i = 0;
    // don't use 'for' loop because 'continue' will skip $key assignement but increase '$i++'
    while ($i<=$lenm1_key) {
        $mtr = mt_rand(0,$lenm1_chr_pool);
        // don't repeat a random value
        if ($repeat=='false') {
	        if (in_array($mtr, $atmp_mtr)) { continue; }
        }
        $atmp_mtr[] = $mtr;
        $key .= $chr_pool[$mtr];
        $i++;
    }
    return $key;
}/*}}}*/
function ses_() {/*{{{*/
}/*}}}*/
function sql_() {/*{{{*/


}/*}}}*/
function sty_gen() {/*{{{*/
    // add reasonable style defaults, including forms
$o =<<< EOT
<style>
pre {
    font: 8pt Consolas;
    background: whitesmoke;
}

.alert { color: red; }
.warn  { color: yellow; }
.info  { color: green; }
#clear { clear: both; }
#tbl_sel {
    font: 9pt Consolas;
    border-spacing: 1px;
}
#tbl_sel th {
    background: gainsboro;
}
#tbl_sel td {
    background: whitesmoke;
    padding: 0px;
}
</style>
EOT;
    return $o;
}/*}}}*/
function tbl_sel($a) {/*{{{*/
    $a = cst_arr($a, 'sql');
    $sql = $a['sql'];
    $r = dba_sel($sql);
    $field = $r['field'];
    $data = $r['data'];

    $out  = '<table id="tbl_sel">';
    // table head
    $out .= '<tr>';
    foreach ($field as $f) {
        // only need $key
        $table_head = ucfirst($f['name']);
        $out .= "<th>$table_head</th>";
    }
    $out .= '</tr>';
    // table data
    foreach ($data as $d) {
        $out .= '<tr>';
        foreach ($d as $value) {
            $out .= "<td>$value</td>";
        }
        $out .= '</tr>';
    }
    $out .= '</table>';
    return $out;
}/*}}}*/
function tpl_() {/*{{{*/

}/*}}}*/
function va_trm_exp($a) {/*{{{*/
	// trim explode (explode then trim)
	$a = cst_arr($a, 'tnt');
	$array_str = $a['tnt'];
	$delimiter = cst_def($a, ',', 'delimiter');
	$array = explode($delimiter, $array_str);
	$array_trimmed = array();
	foreach ($array as $value) {
		$array_trimmed[] = trim($value);
	}
	unset($a);
	unset($array);
	return $array_trimmed;
}/*}}}*/
function va_str_sla($a) {/*{{{*/
    // va string strip slashes
    if (get_magic_quotes_gpc()) {
        $a = stripslashes($a);
    }
    return $a;
}/*}}}*/
function va_str_wrd($a) {/*{{{*/
    // va string word summary: chunk of string with word boundary
    $a = cst_arr($a, 'string');
    $string = $a['string'];
    $len    = cst_def($a, 100, 'len');
    $cont   = cst_def($a, '...', 'cont');
    $strip  = cst_def($a, 'yes', 'strip');

    // strip tags
    if ($strip=='yes') {
        $string = strip_tags($string);
    }

    if (strlen($string) <= $len) {
        return $string;
    }

    $r = substr($string, 0, $len);
    $r = preg_replace('/\s\w+$/', '', $r);
    $r = preg_replace('/\s*$/', '', $r);
    $r = preg_replace('/[!,.]$/', '', $r);
    $r = $r.' '.$cont;

    return $r;
}/*}}}*/
function va_br($param='') {/*{{{*/
	// provides html br inluding a newline
	$out = "<br />\n";
	if ($param) {
		for ($i=1; $i<$param; $i++)
			$out .= "<br />\n";
	}
	return $out;
}/*}}}*/
function va_controller($a) {/*{{{*/
	$a = cst_arr($a, 'view');
	$view = cst_def($a, '', 'view');
	$task = cst_def($a, '', 'task');

	// fn_prefix
	// va_test	correct
	// va_test_	incorrect
	$fn_prefix = cst_def($a, '', 'fn_prefix');

	$template = cst_def($a, '', 'template');
	$echo = cst_def($a, false, 'echo');
	$debug = cst_def($a, true, 'debug');

	if ($task && $view) {
		$fn_to_execute = $fn_prefix.'_'.$view.'_'.$task;
	} else if ($view) {
		$fn_to_execute = $fn_prefix.'_'.$view;
	} else {
		$fn_to_execute = $fn_prefix;
	}

	if ($template) {
		if (function_exists($fn_to_execute)) {
			$o = $fn_to_execute();
			$template_content = file_get_contents($template);
			foreach ($o as $key => $value) {
				$template_content = preg_replace("/<%[\s]*{$key}[\s]*%>/", $value, $template_content);
			}
			echo $template_content;
		} else if ($debug) {
			echo "?$fn_to_execute";
		}
		return true;
	} else if ($echo==true) {
		if (function_exists($fn_to_execute)) {
			echo $fn_to_execute();
		} else if ($debug) {
			echo "?$fn_to_execute";
		}
		return true;
	} else {
		if (function_exists($fn_to_execute)) {
			return $fn_to_execute();
		} else if ($debug) {
			echo "?$fn_to_execute";
		}
		return true;
	}
}/*}}}*/
function va_div_clr() {/*{{{*/
	$out = "<div class='clear'></div>\n";
	return $out;
}/*}}}*/
function va_html_ent($a) {/*{{{*/
    // htmlentities always with UTF-8 encoding
    $a = htmlentities($a, ENT_COMPAT, 'UTF-8');
    return $a;
}/*}}}*/
function va_p2a($p='') {/*{{{*/
    if (!$p) {
        return false;
    }
    $a = array();
    $param_items = explode(";", $p);
    foreach ($param_items as $pi) {
        $pi = trim($pi);
        $kv = explode(":", $pi);
        $key = trim($kv[0]);
        $value = trim($kv[1]);
        $a[$key] = $value;
    }
    return $a;
}/*}}}*/
function va_t2u($a) {/*{{{*/
    // title 2 url
    $a = cst_arr($a, 'title');
    $title = $a['title'];
    //$url = preg_replace('/\s/', '-', $title);
    $url = urlencode($title);
    //$url = htmlentities($title, ENT_QUOTES);
    $url = strtolower($url);
    return $url;
}/*}}}*/
function va_u2t($a) {/*{{{*/
    // url 2 title
    $a = cst_arr($a, 'url');
    $url = $a['url'];
    //$title = preg_replace('/\-/', ' ', $url);
    $title = urldecode($url);
    //$title = html_entity_decode($url, ENT_QUOTES);
    $title = ucwords($title);
    // todo respect grammar rules: a, in, it, of, etc should not be capitalized
    return $title;
}/*}}}*/
function vad_eml($value) {/*{{{*/
    if (!preg_match("/^[\ a-z0-9._-]+@[a-z0-9.-]+\.[a-z]{2,6}$/i", $value)) {
        return false;
    }
    return true;
}/*}}}*/
function vad_emr($value) {/*{{{*/
	// validated email registration

	// $valid can mutate. returns genuine true or error code string (false true) or genuine false
	$valid = false;

	// 1. check valid email - if false return error_0
	if (vad_eml($value)) {
		$valid = true;
	} else {
		return 'error_0';
	}

	// 2. look up in database if email exists - if true return error_1
	$query = "select email from `user` where email='$value'";
	$result = mysql_query($query);
	// num_result!
	if (mysql_num_rows($result)) {
		return 'error_1';
	} else {
		$valid = true;
	}

	return $valid;
}/*}}}*/
function vad_ath($a) {/*{{{*/
    // validation authentication
    $user_field = $a['user_field'];
    $user_value = $a['user_value'];
    $pass_field = $a['pass_field'];
    $pass_value = $a['pass_value'];
    $table = $a['table'];
    if (!$user_value || !$pass_value || !$table) { return false; }
    if ($user_value && $pass_value && $table) {
        $data = dba_sel("select * from `$table` where `$user_field`='$user_value' && `$pass_field`='$pass_value'");
        $data = $data['data'][0];
        if ($pass_value==$data[$pass_field]) {
            $r['bvalid'] = true;
            $r['data'] = $data;
            return $r;
        }
    }
    // fail by default
    return false;
}/*}}}*/
function vad_nem($value) {/*{{{*/
    // trim value. we don't want just spaces.
    $value = trim($value);
    if (!$value) {
        return false;
    }
    return true;
}/*}}}*/
function vad_nem_arr($values) {/*{{{*/
	if (!$values) {
		return false;
	}
	return true;
}/*}}}*/
function vad_unr($value) {/*{{{*/
	// validated username registration

	// $valid can mutate. returns genuine true or error code string (false true) or genuine false
	$valid = false;

	// 1. check non-empty username - if false return error_0
	if (vad_nem($value)) {
		$valid = true;
	} else {
		return 'error_0';
	}

	// 2. look up in database if username exists - if true return error_1
	$query = "select `username` from `user` where `username`='$value'";
	$result = mysql_query($query);
	// num_result!
	if (mysql_num_rows($result)) {
		return 'error_1';
	} else {
		$valid = true;
	}

	// username should contain only english alphabets and numbers
	if (!preg_match('/[a-z0-9.-]/i', $value)) {
		return 'error_2';
	} else {
		$valid = true;
	}

	return $valid;
}/*}}}*/
function vld_rca() {/*{{{*/
    // validate recaptcha
    $privatekey = RCA_PVT_KEY;
    $resp = recaptcha_check_answer ($privatekey,
                                    $_SERVER["REMOTE_ADDR"],
                                    $_POST["recaptcha_challenge_field"],
                                    $_POST["recaptcha_response_field"]);
    /*
    object(ReCaptchaResponse)#1 (2) {
        ["is_valid"]=> bool(false)
        ["error"]=> string(21) "incorrect-captcha-sol"
    }
    */

    return $resp->is_valid;
}/*}}}*/
function var_dmp($var) {/*{{{*/
    // prints and echoes directly because var_dump does so
    echo  "<pre>";
    echo var_dump($var);
    echo "</pre>";
}/*}}}*/
function wps_tpl() {/*{{{*/
    if ( function_exists('register_sidebar') ) {
        register_sidebar(array(
            'before_widget' => '<li id="%1$s" class="widget %2$s">',
            'after_widget' => '</li>',
            'before_title' => '<h2 class="widgettitle">',
            'after_title' => '</h2>',
        ));
    }
}/*}}}*/
rc0();
rc1();
?>