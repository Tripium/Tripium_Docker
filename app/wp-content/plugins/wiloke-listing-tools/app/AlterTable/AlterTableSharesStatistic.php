<?php

namespace WilokeListingTools\AlterTable;

class AlterTableSharesStatistic implements AlterTableInterface
{
    public static $tblName = 'wilcity_shares_statistic';
    public $version = '1.0';
    use TableExists;

    public function __construct()
    {
        $this->createTable();
        //        add_action('plugins_loaded', [$this, 'createTable']);
    }

    public function createTable()
    {
        if ($this->isTableExists()) {
            return false;
        }

        global $wpdb;
        $tblName        = $wpdb->prefix.self::$tblName;
        $postTbl        = $wpdb->posts;
        $charsetCollect = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $tblName(
	  		ID bigint(20) NOT NULL AUTO_INCREMENT,
			objectID bigint(20) UNSIGNED NOT NULL,
			countShares int NOT NULL,
			date DATE NOT NULL,
			FOREIGN KEY (objectID) REFERENCES $postTbl(ID) ON DELETE CASCADE,
			PRIMARY KEY (ID)
		) $charsetCollect";

        require_once ABSPATH.'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option(self::$tblName, $this->version);
    }

    public function deleteTable()
    {
        // TODO: Implement deleteTable() method.
    }
}
