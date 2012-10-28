<?php
/**
 * @todo the obvious: clean up the mess
 * @todo nicer form design
 */

if(!defined('WP2TM_VERSION')) {
	exit;
}

$options = wp2tm_getOptions(true);

?>

<script type="text/javascript">
	// <![CDATA[ 
	
	var wp2tm_relTableId = 'related-tags';
					
	function wp2tm_insertAfter(newElm, referenceElm) {
		referenceElm.parentNode.insertBefore(newElm,referenceElm.nextSibling);
	}
	
	function wp2tm_removeRelatedTagRow(rowId) {
		var rowElm = document.getElementById(rowId);
		var tblElm = document.getElementById(wp2tm_relTableId);
		tblElm.getElementsByTagName('tbody').item(0).removeChild(rowElm);
	}
	
	function wp2tm_addRelatedTagRow(tagId, rowNumber) {
		// the quick and dirty and easy way ... i must love DOM
		var relTableElm = document.getElementById(wp2tm_relTableId);
		var trElm = document.getElementById('related-tag-' + tagId + '-' + rowNumber);
		if(typeof(trElm) == 'undefined' || typeof(relTableElm) == 'undefined') {
			return;
		}
		// new TR/TD elms
		var newRowNumber = rowNumber + 1;
		var newTr = document.createElement('tr');
		newTr.id = 'related-tag-' + tagId + '-' + newRowNumber;
		newTr.className = trElm.className;
		var dispNameTdElm  = document.createElement('td');
		var uriTdElm       = document.createElement('td');
		var useAsIdTdElm   = document.createElement('td');
		var deleteRowTdElm = document.createElement('td');
		// new input elms
		var dispNameInputElm = document.createElement('input');
		var uriInputElm = document.createElement('input');
		var useAsIdInputElm = document.createElement('input');
		var deleteRowBtn = document.createElement('button');
		// attr on input elms
		dispNameInputElm.type  = 'text';
		dispNameInputElm.name  = 'wp2tm_related_tags[' + tagId + '][' + newRowNumber + '][displayName]';
		dispNameTdElm.appendChild(dispNameInputElm);
		uriInputElm.type = 'text';
		uriInputElm.name = 'wp2tm_related_tags[' + tagId + '][' + newRowNumber + '][uri]';
		uriTdElm.appendChild(uriInputElm);
		useAsIdInputElm.type = 'checkbox';
		useAsIdInputElm.name = 'wp2tm_related_tags[' + tagId + '][' + newRowNumber + '][useAsSubjectIndicator]';
		useAsIdTdElm.appendChild(useAsIdInputElm);
		useAsIdTdElm.className = 'chkBxTd';
		deleteRowBtn.appendChild(document.createTextNode('-'));
		deleteRowBtn.onclick = function() { wp2tm_removeRelatedTagRow(newTr.id); };
		deleteRowTdElm.className = 'btnTd';
		deleteRowTdElm.appendChild(deleteRowBtn);
		// add new tds
		newTr.appendChild(document.createElement('td'));
		newTr.appendChild(dispNameTdElm);
		newTr.appendChild(uriTdElm);
		newTr.appendChild(useAsIdTdElm);
		newTr.appendChild(deleteRowTdElm);
		wp2tm_insertAfter(newTr, trElm);
		
	}
	
	// ]]>
</script>

<style type="text/css">
<!--
	
	#wp2tm-options kbd {
		font-size: 1.4em;
	}

	#wp2tm-options p {
		max-width: 700px;
	}
	
	#wp2tm-options div.submit {
		margin: 1em;
		text-align: right;
	}
	
	#wp2tm-options fieldset {
		border: 1px solid #999;
		margin-bottom: 2em;
		padding: 2em;
		background-color: #fefefe;
	}
	
	#wp2tm-options fieldset h3 {
		margin: 0;
	}
	
	#related-tags button {
		padding: 0.3em 0.5em;
	}
	
	table#related-tags, table#related-tags tr, table#related-tags th, table#related-tags td {
		border-collapse: collapse;
		border: 0;
	}
	
	table#related-tags {
		width: 100%;
		max-width: 1200px;
		margin: 1em 0 3em;
		border: 1px solid #ccc;
	}
	
	#related-tags thead tr {
		color: black;
		background-color: #ddd;
	}
	
	table#related-tags tbody tr {
		background-color: #eee;
		color: inherit;
	}
	
	#related-tags tbody tr.alt {
		color: inherit;
		background-color: #fefefe;
	}

	table#related-tags tbody tr:hover {
		background-color: #bbb;
		color: inherit;
	}
	
	#related-tags th {
		padding: 0.5em;
	}
	 
	#related-tags td {
		padding: 0.6em;
	}
	
	#related-tags input[type="text"] {
		width: 95%;
		padding: 0.35em;
		font-size: 0.9em;
		font-weight: bold;
		background-color: white;
		border: 2px inset #999;
	}
	
	#related-tags input[type="text"]:focus {
		color: black;
		background-color: #ffe;
	}
	
	#related-tags .chkBxTd,#related-tags .btnTd {
		text-align: center;
	}
	
	.wp2tm-key-value-pair {
		width: 600px;
		clear: both;
		padding: 1em 0;
	}
	
	.wp2tm-label {
		width: 49%;
		float: left;
	}
	
	.wp2tm-value {
		float: right;
		text-align: left;
		width: 49%;
	}
	
	#wp2tm-options .note {
		font-size: 0.8em;
		margin: 0 2em;
		font-style: italics;
	}
	
-->
</style>

<p>
	You may control the <a href="http://www.topicobserver.com/wp2tm/">WP2TM</a> 
	generated <acronym title="XML Topic Maps">XTM</acronym> by adjusting the settings below.
</p>

<form method="post" action="<?php echo htmlentities($_SERVER['REQUEST_URI']); ?>" id="wp2tm-options">
	<div class="submit">
		<input type="hidden" name="update_wp2tm_options" value="1" />
		<input type="submit" name="submit" value="Save WP2TM Settings" />
	</div>
	<fieldset>
		<h3>Settings</h3>
		<div class="wp2tm-key-value-pair">
			<div class="wp2tm-label">
				<label for="wp2tm_no_posts_in_feed">Maximum number of posts in site feed</label>
			</div>
			<div class="wp2tm-value">
				<input type="text" 
					   name="wp2tm_no_posts_in_feed" 
				       maxlength="3" size="2"
					   value="<?php echo (int)$options['wp2tm_no_posts_in_feed']; ?>" />
			</div>
		</div>
		<div class="wp2tm-key-value-pair">
			<div class="wp2tm-label">
				<label for="wp2tm_max_length_description">Maximum post description length (0 or less = no limit)</label>
			</div>
			<div class="wp2tm-value">
				<input type="text" name="wp2tm_max_length_description" 
					   maxlength="6" size="2"
					   value="<?php echo (int)$options['wp2tm_max_length_description']; ?>" />
			</div>
		</div>		
		<div class="wp2tm-key-value-pair">
			<div class="wp2tm-label">
				<label for="wp2tm_enable_post_feed">Enable XTM fragment for individual posts</label>
			</div>
			<div class="wp2tm-value">
				<input type="checkbox" name="wp2tm_enable_post_feed" value="1"
					   id="wp2tm_enable_post_feed"
					   <?php echo $options['wp2tm_enable_post_feed'] == 1 ? ' checked="checked" ' : ''; ?> />
			</div>
		</div>
		<div class="wp2tm-key-value-pair">
			<div class="wp2tm-label">
				<label for="wp2tm_enable_footer_links">Enable XTM feed links in template footers</label>
			</div>
			<div class="wp2tm-value">
				<input type="checkbox" name="wp2tm_enable_footer_links" value="1"
					   id="wp2tm_enable_footer_links"
					   <?php echo $options['wp2tm_enable_footer_links'] == 1 ? ' checked="checked" ' : ''; ?> />
			</div>	
		</div>
	</fieldset>
	<fieldset>
		<h3>Advanced Settings</h3>
		
		<div>
			<div>
				<h4>Exclude posts from the following categories:</h4>
			</div>
			<?php
			$categoryList = get_categories('hide_empty=false&hierarchical=true');
			foreach($categoryList as $category) {
			?>
			<div style="margin-left:2.5em">
				<input type="checkbox" 
					   name="wp2tm_exclude_categories[]"  
					   value="<?php echo $category->cat_ID; ?>" 
					   id="cat<?php echo $category->cat_ID; ?>"
					   <?php echo in_array($category->cat_ID, $options['wp2tm_exclude_categories']) ? ' checked="checked"' : ''; ?>
					   />
				<label for="cat<?php echo $category->cat_ID; ?>">
					<?php echo htmlentities($category->cat_name); ?>
				</label>
			</div>
			<?php }	?>
		</div>
		
		<div>
			<h4>
				Relate tags to other blogs' tags 
				(<a href="http://www.topicobserver.com/wp2tm/features/#related-tags" target="_blank">help</a>)
			</h4>
			<p>
				<kbd>*</kbd> denotes required field.
			</p>
			<table id="related-tags" summary="Form controls for associating blog tags with other tags">
				<thead>
					<tr>
						<th style="width: 17%">Tag</th>
						<th style="width: 22%">Related Tag Display Name</th>
						<th style="width: 33%">Related Tag URI <kbd>*</kbd></th>
						<th style="width: 10%">Use as ID ref</th>
						<th style="width: 5%">More</th>
					</tr>
				</thead>
				<tbody>
				<?php
				$tagList = get_tags();
				foreach($tagList as $_indx => $tag) {
					if(array_key_exists($tag->term_id, $options['wp2tm_related_tags'])) {
						$noRelatedTags = count($options['wp2tm_related_tags'][$tag->term_id]);
						for($i=0; $i < $noRelatedTags; $i++) {
							$relatedTagData = $options['wp2tm_related_tags'][$tag->term_id][$i];
							$rowId = 'related-tag-' . $tag->term_id . '-' . $i;
							?>
							
							<tr id="<?php echo $rowId; ?>"  
							    <?php echo $_indx % 2 > 0 ? ' class="alt"' : '';  ?>>
								<td>
									<strong><?php echo $i == 0 ? htmlentities($tag->name, ENT_COMPAT, 'utf-8') : '&nbsp;'; ?></strong>
								</td>
								<td>
									<input type="text" 
										   name="wp2tm_related_tags[<?php echo $tag->term_id; ?>][<?php echo $i; ?>][displayName]" 
										   value="<?php echo htmlentities($relatedTagData['displayName'], ENT_COMPAT, 'utf-8'); ?>" />							
								</td>
								<td>
									<input type="text" 
									       name="wp2tm_related_tags[<?php echo $tag->term_id; ?>][<?php echo $i; ?>][uri]" 
										   value="<?php echo htmlentities($relatedTagData['uri'], ENT_COMPAT, 'utf-8'); ?>" 
										   class="required" />
								</td>
								<td class="chkBxTd">
									<input type="checkbox" 
										   name="wp2tm_related_tags[<?php echo $tag->term_id; ?>][<?php echo $i; ?>][useAsSubjectIndicator]" 
										   value="1" 
									<?php echo $relatedTagData['useAsSubjectIndicator'] == 1 ? ' checked="checked"' : ''; ?> />
								</td>
								<?php if($i == 0) { ?>
									<td class="btnTd">
											<button title="Add more tags"
												    onclick="wp2tm_addRelatedTagRow(<?php echo $tag->term_id; ?>, <?php echo $i; ?>); return false;">+</button>
									</td>
								<?php } else { ?>
									<td class="btnTd">
											<button title="Remove tag"
												    onclick="wp2tm_removeRelatedTagRow('<?php echo $rowId; ?>'); return false;">-</button>
									</td>
								<?php } ?>
							</tr>
							
							<?php
						}
						
					} else {
						
					?>
						<tr id="related-tag-<?php echo $tag->term_id; ?>-0" <?php echo $_indx % 2 > 0 ? ' class="alt"' : '';  ?>>
							<td>
								<strong><?php echo htmlentities($tag->name, ENT_COMPAT, 'utf-8'); ?></strong>
							</td>
							<td>
								<input type="text" 
								   	   name="wp2tm_related_tags[<?php echo $tag->term_id; ?>][0][displayName]" 
								   	   value="" />
							</td>							
							<td>
								<input type="text"
									   name="wp2tm_related_tags[<?php echo $tag->term_id; ?>][0][uri]" 
									   value="" />
							</td>
							<td class="chkBxTd">
								<input type="checkbox" 
									   name="wp2tm_related_tags[<?php echo $tag->term_id; ?>][0][useAsSubjectIndicator]" 
									   value="1" />
							</td>
							<td class="btnTd">
									<button title="Add more tags"
										    onclick="wp2tm_addRelatedTagRow(<?php echo $tag->term_id; ?>, 0); return false;">+</button>
						</td>
						</tr>
				<?php 
					}
				} 
				?>
				</tbody>
			</table>
			
		</div>
		
	</fieldset>
	
	<div class="submit">
		<input type="hidden" name="update_wp2tm_options" value="1" />
		<input type="submit" name="submit" value="Save WP2TM Settings" />
	</div>
	
</form>