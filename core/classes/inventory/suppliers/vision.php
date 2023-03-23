<?php

/**
 * Parent class for 2 child classes (vision wheels CA/US). Runs from the same
 * file. Do the same thing, just switch out a column name to grab the
 * locale specific value.
 *
 * Class SIS_Vision
 */
Abstract Class SIS_Vision extends Supplier_Inventory_Supplier {

    // note on columns:
    // also note that the total columns may have some lowercase letters
    //INTQNTAL – Inventory Quantity Alabama.
    //INTQNTCA – Inventory Quantity California
    //INTQNTIN – Inventory Quantity Indiana
    //INTQNTTX – Inventory Quantity Texas
    //INTQNTNC – Inventory Quantity North Carolina
    //INTQNTON – Inventory Quantity Ontario
    //INTQNTVW – Total available (US)
    //INTQNTVWCAD – Total Available (CAD)
    //DISCONTINUED

    /**
     * Set this in subclass. The column name in the CSV file provided by
     * the supplier based on locale (CA or US). Should be safe to have
     * this case insensitive.
     *
     * @var
     */
    public $stock_column_name_in_csv;

    /**
     * Set $this->ftp to the return value of this method in the constructor.
     *
     * @return FTP_Get_Csv
     */
    public static function vision_ftp_object() {

        $ftp                   = new FTP_Get_Csv();
        $ftp->method           = 'sftp';
        $ftp->host             = self::$our_own_ftp_server_host;
        $ftp->username         = 'u95793629-vision';
        $ftp->password         = '##removed';
        $ftp->remote_file_name = 'inv-update.csv';

        return $ftp;
    }

    /**
     *
     */
    public function prepare_for_import() {

        $this->ftp->run();

        $col_map = [
            'part_number' => 'vchrPartNumber',
            'discontinued' => 'DISCONTINUED',
            'stock' => $this->stock_column_name_in_csv,
        ];

        // when looking for column names, the object below should do it in a case-insensitive way
        $this->csv = new CSV_To_Array( $this->ftp->get_local_full_path(), $col_map );

        // delete local copy of file
        $this->ftp->unlink();

        // format our array the way the importer expects it
        $this->array = $this->csv->array ? array_filter( array_map( function ( $row ) {

            $dc = trim( $row[ 'discontinued' ] );

            if ( $dc === 1 || $dc === "1" || strtolower( $dc ) == "true" ) {
                // trigger the array filter to remove the value
                return null;
            } else {
                $stock = self::convert_qty_value_to_int( $row[ 'stock' ] );
            }

            $part_number = trim( $row[ 'part_number' ] );

            return array(
                'part_number' => $part_number,
                'stock' => $stock,
            );

        }, $this->csv->array ) ) : [];

        // the array filtering going on below is filtering for other reasons..
        // hard to explain. Just make sure to do it above also.
        $this->array = self::array_map_and_filter( $this->array );
    }

    /**
     * @return string
     */
    public function get_admin_info_extra_column() {
        return 'Read from column (case insensitive): ' . $this->stock_column_name_in_csv;
    }
}
