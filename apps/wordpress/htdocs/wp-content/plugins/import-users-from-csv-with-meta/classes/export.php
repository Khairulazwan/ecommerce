<?php
if ( ! defined( 'ABSPATH' ) ) exit; 

class ACUI_Exporter{
	private $path_csv;
	private $user_data;

	function __construct(){
		$upload_dir = wp_upload_dir();

		$this->path_csv = $upload_dir['basedir'] . "/export-users.csv";
		$this->user_data = array( "user_login", "user_email", "source_user_id", "user_pass", "user_nicename", "user_url", "user_registered", "display_name" );
		$this->woocommerce_default_user_meta_keys = array( 'billing_first_name', 'billing_last_name', 'billing_email', 'billing_phone', 'billing_country', 'billing_address_1', 'billing_city', 'billing_state', 'billing_postcode', 'shipping_first_name', 'shipping_last_name', 'shipping_country', 'shipping_address_1', 'shipping_address_2', 'shipping_city', 'shipping_state', 'shipping_postcode' );
		$this->other_non_date_keys = array( 'shipping_phone' );

		add_action( 'wp_ajax_acui_export_users_csv', array( $this, 'export_users_csv' ) );
		add_filter( 'acui_export_get_key_user_data', array( $this, 'filter_key_user_id' ) );
		add_filter( 'acui_export_non_date_keys', array( $this, 'get_non_date_keys' ), 1, 1 );
		add_filter( 'acui_export_columns', array( $this, 'maybe_order_columns_alphabetacally' ), 10, 2 );
        add_filter( 'acui_export_columns', array( $this, 'maybe_order_columns_filtered_columns_parameter' ), 11, 2 );
		add_filter( 'acui_export_data', array( $this, 'maybe_double_encapsulate_serialized_values' ), 9 - 1, 5 );
        add_filter( 'acui_export_data', array( $this, 'maybe_order_row_alphabetically' ), 10, 5 );
        add_filter( 'acui_export_data', array( $this, 'maybe_order_row_filtered_columns_parameter' ), 11, 5 );
	}

	public static function admin_gui(){
		$roles = ACUI_Helper::get_editable_roles();
	?>
	<h3 id="acui_export_users_header"><?php _e( 'Export users', 'import-users-from-csv-with-meta' ); ?></h3>
	<form id="acui_export_users_wrapper" method="POST" target="_blank" enctype="multipart/form-data" action="<?php echo admin_url( 'admin-ajax.php' ); ?>">
		<table class="form-table">
			<tbody>
				<tr id="acui_role_wrapper" valign="top">
					<th scope="row"><?php _e( 'Role', 'import-users-from-csv-with-meta' ); ?></th>
					<td>
						<select name="role">
							<option value=''><?php _e( 'All roles', 'import-users-from-csv-with-meta' ); ?></option>
						<?php foreach ( $roles as $key => $value ): ?>
							<option value='<?php echo $key; ?>'><?php echo $value; ?></option>
						<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr id="acui_user_created_wrapper" valign="top">
					<th scope="row"><?php _e( 'User created', 'import-users-from-csv-with-meta' ); ?></th>
					<td>
						<label for="from">from <input name="from" type="date" value=""/></label>
						<label for="to">to <input name="to" type="date" value=""/></label>
					</td>
				</tr>
				<tr id="acui_delimiter_wrapper" valign="top">
					<th scope="row"><?php _e( 'Delimiter', 'import-users-from-csv-with-meta' ); ?></th>
					<td>
						<select name="delimiter">
							<option value='COMMA'><?php _e( 'Comma', 'import-users-from-csv-with-meta' ); ?></option>
							<option value='COLON'><?php _e( 'Colon', 'import-users-from-csv-with-meta' ); ?></option>
							<option value='SEMICOLON'><?php _e( 'Semicolon', 'import-users-from-csv-with-meta' ); ?></option>
							<option value='TAB'><?php _e( 'Tab', 'import-users-from-csv-with-meta' ); ?></option>
						</select>
					</td>
				</tr>
				<tr id="acui_timestamp_wrapper" valign="top">
					<th scope="row"><?php _e( 'Convert timestamp data to date format', 'import-users-from-csv-with-meta' ); ?></th>
					<td>
						<input type="checkbox" name="convert_timestamp" id="convert_timestamp" value="1" checked="checked">
						<input name="datetime_format" id="datetime_format" type="text" value="Y-m-d H:i:s"/> 
                        <span class="description"><a href="https://www.php.net/manual/en/datetime.formats.php"><?php _e( 'accepted formats', 'import-users-from-csv-with-meta' ); ?></a> <?php _e( 'If you have problems and you get some value exported as a date that should not be converted to date, please deactivate this option. If this option is not activated, datetime format will be ignored.', 'import-users-from-csv-with-meta' ); ?></span>
					</td>
				</tr>
				<tr id="acui_order_fields_alphabetically_wrapper" valign="top">
					<th scope="row"><?php _e( 'Order fields alphabetically', 'import-users-from-csv-with-meta' ); ?></th>
					<td>
						<input type="checkbox" name="order_fields_alphabetically" value="1">
						<span class="description"><?php _e( "Order all columns alphabetically to check easier your data. First two columns won't be affected", 'import-users-from-csv-with-meta' ); ?></span>
					</td>
				</tr>
                <tr id="acui_order_fields_double_encapsulate_serialized_values" valign="top">
					<th scope="row"><?php _e( 'Double encapsulate serialized values', 'import-users-from-csv-with-meta' ); ?></th>
					<td>
						<input type="checkbox" name="double_encapsulate_serialized_values" value="1">
						<span class="description"><?php _e( "Serialized values sometimes can have problems being displayed in Microsoft Excel or LibreOffice, we can double encapsulate this kind of data but you would not be able to import this data beucase instead of serialized data it would be managed as strings", 'import-users-from-csv-with-meta' ); ?></span>
					</td>
				</tr>
				<tr id="acui_download_csv_wrapper" valign="top">
					<th scope="row"><?php _e( 'Download CSV file with users', 'import-users-from-csv-with-meta' ); ?></th>
					<td>
						<input class="button-primary" type="submit" value="<?php _e( 'Download', 'import-users-from-csv-with-meta'); ?>"/>
					</td>
				</tr>
			</tbody>
		</table>
		<input type="hidden" name="action" value="acui_export_users_csv"/>
		<?php wp_nonce_field( 'codection-security', 'security' ); ?>				
	</form>

	<script type="text/javascript">
	jQuery( document ).ready( function( $ ){
		$( "input[name='from']" ).change( function() {
			$( "input[name='to']" ).attr( 'min', $( this ).val() );
		})

		$( '#convert_timestamp' ).on( 'click', function() {
			check_convert_timestamp_checked();
		});

		function check_convert_timestamp_checked(){
			if( $('#convert_timestamp').is(':checked') ){
				$( '#datetime_format' ).prop( 'disabled', false );
			} else {
				$( '#datetime_format' ).prop( 'disabled', true );
			}
		}
	} )
	</script>
	<?php
	}

	static function is_valid_timestamp( $timestamp ){
		return ( (string) (int) $timestamp === $timestamp ) && ( $timestamp <= PHP_INT_MAX ) && ( $timestamp >= ~PHP_INT_MAX );
	}

	function get_non_date_keys( $non_date_keys ){
		return array_merge( $non_date_keys, $this->user_data, $this->woocommerce_default_user_meta_keys, $this->other_non_date_keys );
	}

	function maybe_order_columns_alphabetacally( $row, $args ){
        if( !$args['order_fields_alphabetically'] )
			return $row;
		
		$first_two_columns = array_slice( $row, 0, 2 );
		$to_order_columns = array_unique( array_slice( $row, 2 ) );
		sort( $to_order_columns, SORT_LOCALE_STRING );

        return array_merge( $first_two_columns, $to_order_columns );
	}

    function maybe_order_columns_filtered_columns_parameter( $row, $args ){
        return ( !is_array( $args['filtered_columns'] ) || count( $args['filtered_columns'] ) == 0 ) ? $row : $args['filtered_columns'];
    }

	function maybe_order_row_alphabetically( $row, $user, $datetime_format, $columns, $args ){
        if( !$args['order_fields_alphabetically'] )
			return $row;

        $row_sorted = array();
        foreach( $columns as $field ){
            $row_sorted[ $field ] = $row[ $field ];
        }

        return $row_sorted;
	}

    function maybe_order_row_filtered_columns_parameter( $row, $user, $datetime_format, $columns, $args ){
        if( !is_array( $args['filtered_columns'] ) || count( $args['filtered_columns'] ) == 0 )
			return $row;

        $row_sorted = array();
        foreach( $args['filtered_columns'] as $field ){
            $row_sorted[ $field ] = $row[ $field ];
        }

        return $row_sorted;
	}

    function maybe_double_encapsulate_serialized_values( $row, $user, $datetime_format, $columns, $args ){
        if( !$args['double_encapsulate_serialized_values'] )
			return $row;

        foreach( $columns as $field ){
            if( is_serialized( $row[ $field ] ) )
                $row[ $field ] = '"' . $row[ $field ] . '"';
        }

        return $row;
	}    

	static function clean_bad_characters_formulas( $value ){
		if( strlen( $value ) == 0 )
			return $value;

		$bad_characters = array( '+', '-', '=', '@' );
		$first_character = substr( $value, 0, 1 );
		if( in_array( $first_character, $bad_characters ) )
			$value = "\\" . $first_character . substr( $value, 1 );

		return $value;
	}

	static function prepare( $key, $value, $datetime_format, $user = 0 ){
		$timestamp_keys = apply_filters( 'acui_export_timestamp_keys', array( 'wc_last_active' ) );
		$non_date_keys = apply_filters( 'acui_export_non_date_keys', array() );
		$original_value = $value;

		if( $key == 'role' ){
			return self::get_role( $user );
		}
		if( is_array( $value ) || is_object( $value ) ){
			return serialize( $value );
		}
		elseif( in_array( $key, $non_date_keys ) || empty( $datetime_format ) ){
			return self::clean_bad_characters_formulas( $value );
		}
		elseif( strtotime( $value ) ){ // dates in datetime format
			return date( $datetime_format, strtotime( $value ) );
		}
		elseif( is_int( $value ) && ( ( self::is_valid_timestamp( $value ) && strlen( $value ) > 4 ) || in_array( $key, $timestamp_keys) ) ){ // dates in timestamp format
			return date( $datetime_format, $value );
		}
		else{
			return apply_filters( 'acui_export_prepare', self::clean_bad_characters_formulas( $value ), $original_value );
		}
	}

	static function get_role( $user_id ){
		$user = get_user_by( 'id', $user_id );
		return implode( ',', $user->roles );
	}

    function manage_filtered_columns( $filtered_columns ){
        $filtered_columns = ( is_array( $filtered_columns ) ) ? array_walk( $filtered_columns, 'sanitize_text_field' ) : explode( ',', sanitize_text_field( $filtered_columns ) );

        if( empty( $filtered_columns[0] ) )
            $filtered_columns = array();

        return $filtered_columns;
    }
    
	function export_users_csv(){
		check_ajax_referer( 'codection-security', 'security' );

		if( !current_user_can( apply_filters( 'acui_capability', 'create_users' ) ) )
			wp_die( __( 'Only users who are able to create users can export them.', 'import-users-from-csv-with-meta' ) );

		$role = sanitize_text_field( $_POST['role'] );
		$from = sanitize_text_field( $_POST['from'] );
		$to = sanitize_text_field( $_POST['to'] );
		$delimiter = sanitize_text_field( $_POST['delimiter'] );
		$convert_timestamp = isset( $_POST['convert_timestamp'] ) && !empty( $_POST['convert_timestamp'] );
		$datetime_format = ( $convert_timestamp ) ? sanitize_text_field( $_POST['datetime_format'] ) : '';
		$order_fields_alphabetically = isset( $_POST['order_fields_alphabetically'] ) && !empty( $_POST['order_fields_alphabetically'] );
        $double_encapsulate_serialized_values = isset( $_POST['double_encapsulate_serialized_values'] ) && !empty( $_POST['double_encapsulate_serialized_values'] );
        $filtered_columns = ( isset( $_POST['columns'] ) && !empty( $_POST['columns'] ) ) ? $_POST['columns'] : array();
        $filtered_columns = $this->manage_filtered_columns( $filtered_columns );
        
        switch ( $delimiter ) {
			case 'COMMA':
				$delimiter = ",";
				break;
			
			case 'COLON':
				$delimiter = ":";
				break;

			case 'SEMICOLON':
				$delimiter = ";";
				break;

			case 'TAB':
				$delimiter = "\t";
				break;

            default:
                $delimiter = ",";
                break;
		}

		$data = array();
		$row = array();
		
		// header
		foreach ( $this->get_user_data( $filtered_columns ) as $key ) {
            $row[] = $key;
		}

        if( count( $filtered_columns ) == 0 || in_array( 'role', $filtered_columns ) )
		    $row[] = "role";

		foreach ( $this->get_user_meta_keys( $filtered_columns ) as $key ) {
			$row[] = $key;
		}

		$row = apply_filters( 'acui_export_columns', $row, array( 'order_fields_alphabetically' => $order_fields_alphabetically, 'double_encapsulate_serialized_values' => $double_encapsulate_serialized_values, 'filtered_columns' => $filtered_columns ) );
        $from = apply_filters( 'acui_export_user_registered_from_date', $from );
        $to = apply_filters( 'acui_export_user_registered_to_date', $to );

		$columns = $row;
		$data[] = $row;
		$row = array();

		// data
		$users = $this->get_user_id_list( $role, $from, $to );
		foreach ( $users as $user ) {
			$userdata = get_userdata( $user );

            foreach ( $this->get_user_data( $filtered_columns ) as $key ) {
				$key = apply_filters( 'acui_export_get_key_user_data', $key );
				$row[ $key ] = self::prepare( $key, $userdata->data->{$key}, $datetime_format, $user );
			}

            if( count( $filtered_columns ) == 0 || in_array( 'role', $filtered_columns ) )
			    $row[] = $this->get_role( $user );

			foreach ( $this->get_user_meta_keys( $filtered_columns ) as $key ) {
				$row[ $key ] = self::prepare( $key, get_user_meta( $user, $key, true ), $datetime_format, $user );
			}

            if( count( $filtered_columns ) == 0 || in_array( 'user_email', $filtered_columns ) || in_array( 'user_login', $filtered_columns ) )
			    $row = $this->maybe_fill_empty_data( $row, $user, $filtered_columns );

			$row = apply_filters( 'acui_export_data', $row, $user, $datetime_format, $columns, array( 'order_fields_alphabetically' => $order_fields_alphabetically, 'double_encapsulate_serialized_values' => $double_encapsulate_serialized_values, 'filtered_columns' => $filtered_columns ));
			$data[] = array_values( $row );
			$row = array();
		}

		// export to csv
		$file = fopen( $this->path_csv, "w" );

		foreach ( $data as $line ) {
			fputcsv( $file, $line, $delimiter );
		}

		fclose( $file );

		$fsize = filesize( $this->path_csv ) + 3;
		$path_parts = pathinfo( $this->path_csv );
		header( "Content-type: text/csv;charset=utf-8" );
		header( "Content-Disposition: attachment; filename=\"".$path_parts["basename"]."\"" );
		header( "Content-length: $fsize" );
		header( "Cache-control: privfilefleate" );
		header( "Content-Description: File Transfer" );
    	header( "Content-Transfer-Encoding: binary" );
    	header( "Expires: 0" );
    	header( "Cache-Control: must-revalidate" );
    	header( "Pragma: public" );
    	
    	ob_clean();
    	flush();

    	echo "\xEF\xBB\xBF";
    	readfile( $this->path_csv );

		unlink( $this->path_csv );

		wp_die();
	}

    function get_user_data( $filtered_columns ){
        if( count( $filtered_columns ) == 0 )
            return $this->user_data;
        
        var_export( $filtered_columns );

        $result = array();
        foreach( $this->user_data as $column ){
            if( in_array( $column, $filtered_columns ) )
                $result[] = $column;
        }
        
        return $result;
    }

	function get_user_meta_keys( $filtered_columns ) {
	    global $wpdb;
	    $meta_keys = array();

	    $select = "SELECT distinct $wpdb->usermeta.meta_key FROM $wpdb->usermeta";
	    $usermeta = $wpdb->get_results( $select, ARRAY_A );
	  
	  	foreach ($usermeta as $key => $value) {
			if( $value["meta_key"] == 'role' )
				continue;

            if( count( $filtered_columns ) == 0 || in_array( $value["meta_key"], $filtered_columns ) )
			    $meta_keys[] = $value["meta_key"];
		}

	    return apply_filters( 'acui_export_get_user_meta_keys', $meta_keys );
	}

	function get_user_id_list( $role, $from, $to ){
		$args = array( 'fields' => array( 'ID' ) );

		if( !empty( $role ) )
			$args['role'] = $role;

		$date_query = array();

		if( !empty( $from ) )
			$date_query[] = array( 'after' => $from );
		
		if( !empty( $to ) )
			$date_query[] = array( 'before' => $to );

		if( !empty( $date_query ) ){
			$date_query['inclusive'] = true;
			$args['date_query'] = $date_query;
		}

		$users = get_users( $args );
		$list = array();

	    foreach ( $users as $user ) {
	    	$list[] = $user->ID;
	    }

	    return $list;
	}

	function filter_key_user_id( $key ){
		return ( $key == 'source_user_id' ) ? 'ID' : $key;
	}

	function maybe_fill_empty_data( $row, $user_id, $filtered_columns ){
		if( empty( $row['user_login'] ) || empty( $row['user_email'] ) ){
			$user = new WP_User( $user_id );

			if( $user->ID == 0 )
				return $row;

            if( count( $filtered_columns ) == 0 || in_array( 'user_login', $filtered_columns ) )
			    $row['user_login'] = $user->user_login;

            if( count( $filtered_columns ) == 0 || in_array( 'user_email', $filtered_columns ) )
			    $row['user_email'] = $user->user_email;
		}
		
		return $row;
	}
}

$acui_exporter = new ACUI_Exporter();