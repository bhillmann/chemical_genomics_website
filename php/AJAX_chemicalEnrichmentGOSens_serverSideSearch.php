<?php
    /*
     * Script:    DataTables server-side script for PHP and MySQL
     * Copyright: 2010 - Allan Jardine, 2012 - Chris Wright
     * License:   GPL v2 or BSD (3-point)
     */
     
    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * Easy set variables
     */
     
    /* Array of database columns which should be read and sent back to DataTables. Use a space where
     * you want to insert a non-database field (for example a counter or static image)
     */
    $aColumns = array( 'GO_description', 'GO_enrichment', 'chemical_multiplex', 'chemical_name', 'enrichment_type', 'id', 'lane', 'num_genes', 'num_top_genes', 'overlap_genes', 'p_bonferroni', 'p_fdr', 'p_holm', 'p_sidak', 'p_uncorrected', 'pid', 'ratio_in_pop', 'ratio_in_study', 'sensitivity_type', 'upload_date');

    /* Indexed column (used for fast and accurate table cardinality) */
    $sIndexColumn = "pid";
     
    /* DB table to use */
    $sTable = "chemicalEnrichmentGO";
     
	/* Database connection information */
	require_once '../prv/ps.php';
	$gaSql['user']       = $GLOBALS["UN"];
	$gaSql['password']   = $GLOBALS["PS"];
	$gaSql['db']         = $GLOBALS["DB"];
	$gaSql['server']     = $GLOBALS["SN"];
     
     
    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * If you just want to use the basic configuration for DataTables with PHP server-side, there is
     * no need to edit below this line
     */
     
    /*
     * Local functions
     */
    function fatal_error ( $sErrorMessage = '' )
    {
        header( $_SERVER['SERVER_PROTOCOL'] .' 500 Internal Server Error' );
        die( $sErrorMessage );
    }
 
     
    /*
     * MySQL connection
     */
    if ( ! $gaSql['link'] = mysql_pconnect( $gaSql['server'], $gaSql['user'], $gaSql['password']  ) )
    {
        fatal_error( 'Could not open connection to server' );
    }
 
    if ( ! mysql_select_db( $gaSql['db'], $gaSql['link'] ) )
    {
        fatal_error( 'Could not select database ' );
    }
     
     
    /*
     * Paging
     */
    $sLimit = "";
    if ( isset( $_GET['iDisplayStart'] ) && $_GET['iDisplayLength'] != '-1' )
    {
        $sLimit = "LIMIT ".intval( $_GET['iDisplayStart'] ).", ".
            intval( $_GET['iDisplayLength'] );
    }
     
     
    /*
     * Ordering
     */
    $sOrder = "";
    if ( isset( $_GET['iSortCol_0'] ) )
    {
        $sOrder = "ORDER BY  ";
        for ( $i=0 ; $i<intval( $_GET['iSortingCols'] ) ; $i++ )
        {
            if ( $_GET[ 'bSortable_'.intval($_GET['iSortCol_'.$i]) ] == "true" )
            {
                $sOrder .= $aColumns[ intval( $_GET['iSortCol_'.$i] ) ]."
                    ".($_GET['sSortDir_'.$i]==='asc' ? 'asc' : 'desc') .", ";
            }
        }
         
        $sOrder = substr_replace( $sOrder, "", -2 );
        if ( $sOrder == "ORDER BY" )
        {
            $sOrder = "";
        }
    }
     
     
    /*
     * Filtering
     * NOTE this does not match the built-in DataTables filtering which does it
     * word by word on any field. It's possible to do here, but concerned about efficiency
     * on very large tables, and MySQL's regex functionality is very limited
     */
    $sWhere = "";
    if ( isset($_GET['sSearch']) && $_GET['sSearch'] != "" )
    {
        $sWhere = "WHERE (";
        for ( $i=0 ; $i<count($aColumns) ; $i++ )
        {
            if ( isset($_GET['bSearchable_'.$i]) && $_GET['bSearchable_'.$i] == "true" )
            {
                $sWhere .= $aColumns[$i]." LIKE '%".mysql_real_escape_string( $_GET['sSearch'] )."%' OR ";
            }
        }
        $sWhere = substr_replace( $sWhere, "", -3 );
        $sWhere .= ')';
    }
     
    /* Individual column filtering */
    for ( $i=0 ; $i<count($aColumns) ; $i++ )
    {
        if ( isset($_GET['bSearchable_'.$i]) && $_GET['bSearchable_'.$i] == "true" && $_GET['sSearch_'.$i] != '' )
        {
            if ( $sWhere == "" )
            {
                $sWhere = "WHERE ";
            }
            else
            {
                $sWhere .= " AND ";
            }
            $sWhere .= $aColumns[$i]." LIKE '%".mysql_real_escape_string($_GET['sSearch_'.$i])."%' ";
        }
    }
     

	// Modify the swhere variable for the actual search that we are doing
	if ( $sWhere == "" ){
		$sWhere = "WHERE ";
	} else {
		$sWhere .= " AND ";
	}

	$sWhere .= "sensitivity_type = 'sensitive' AND enrichment_type = 'GO' AND ";

	if($_GET['chemical'] != ""){
		$sWhere .= "chemical_name = '".mysql_real_escape_string($_GET['chemical'])."'";
     
		if($_GET['multiplex'] != "" && $_GET['multiplex'] != "ALL"){
			$sWhere .= " AND chemical_multiplex = '".mysql_real_escape_string($_GET['multiplex'])."'";
		}
		if($_GET['lane'] != "" && $_GET['lane'] != "ALL"){
			$sWhere .= " AND lane = 'lane".mysql_real_escape_string($_GET['lane'])."'";
		}
		if($_GET['replicate'] != "" && $_GET['replicate'] != "ALL"){
			$sWhere .= " AND replicate = '".mysql_real_escape_string($_GET['replicate'])."'";
		}

		/*
		* SQL queries
		* Get data to display
		*/
		$sQuery = "SELECT ".str_replace(" , ", " ", implode(", ", $aColumns))." FROM $sTable $sWhere $sOrder $sLimit";
		$rResult = mysql_query( $sQuery, $gaSql['link'] ) or fatal_error( 'MySQL Error: ' . mysql_errno() );
		//echo $sQuery;
		// Data set length after filtering
		$sQuery = "SELECT COUNT(*) FROM $sTable $sWhere";
		$rResultFilterTotal = mysql_query( $sQuery, $gaSql['link'] ) or fatal_error( 'MySQL Error: ' . mysql_errno() );
		$aResultFilterTotal = mysql_fetch_array($rResultFilterTotal);
		$iFilteredTotal = $aResultFilterTotal[0];

		// Total data set length 
		$sQuery = "SELECT COUNT(".$sIndexColumn.") FROM $sTable";
		$rResultTotal = mysql_query( $sQuery, $gaSql['link'] ) or fatal_error( 'MySQL Error: ' . mysql_errno() );
		$aResultTotal = mysql_fetch_array($rResultTotal);
		$iTotal = $aResultTotal[0];


		//
		// Output
		//
		$output = array(
			"sEcho" => intval($_GET['sEcho']),
			"iTotalRecords" => $iTotal,
			"iTotalDisplayRecords" => $iFilteredTotal,
			"aaData" => array()
		);

		while ( $aRow = mysql_fetch_array( $rResult ) )
		{
			$row = array();
			for ( $i=0 ; $i<count($aColumns) ; $i++ )
			{
				if ( $aColumns[$i] == "version" )
				{
					// Special output formatting for 'version' column 
					$row[] = ($aRow[ $aColumns[$i] ]=="0") ? '-' : $aRow[ $aColumns[$i] ];
				}
				else if ( $aColumns[$i] != ' ' )
				{
					// General output 
					$row[] = $aRow[ $aColumns[$i] ];
				}
			}
			$output['aaData'][] = $row;
		}
	} else {
		// There is no chemical to search and I don't want to search the entire table ever.
		$output = array(
			"sEcho" => intval($_GET['sEcho']),
			"iTotalRecords" => $iTotal,
			"iTotalDisplayRecords" => $iFilteredTotal,
			"aaData" => array()
		);
	}
	echo json_encode( $output );
?>
