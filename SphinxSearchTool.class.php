<?php

class SphinxSearchTool {

    /**
     * perform a search, using $terms
     *
     * using the lame command-line search tool because do not want to consume 
     * system resources running searchd on this shared server, even if it is 
     * possible, which often it is not.
     *
     * @param $terms string
     * @param $limit int
     * @param $offset int
     * @return @array            hash of matching document ids => relevance
     */
    static function search($terms, $limit='', $offset='') {
        $terms = escapeshellarg($terms);
        $terms = preg_replace('#\b-#', ' \\-', $terms); 

        if (!empty($limit))
            $limit = sprintf('--limit %d', $limit);
        if (!empty($offset))
            $offset = sprintf('--offset %d', $offset);

        $cmd = sprintf('%s/search %s %s --config %s %s',
                        SPHINX_BINARY_DIR,
                        $limit, $offset,
                        SPHINX_CONFIG_FILE,
                        $terms);

        exec($cmd, $output, $ret);
        if ($ret == 0 and is_array($output)) {
            $id_list = array();
            foreach ($output as $line) {
                if (preg_match('/^\d+\. document=(\d+)\b.*?weight=(\d+)\b/', $line, $matches)) {
                    $id_list[$matches[1]] = $matches[2];
                }
            }
            return $id_list;
        }
    }

    /**
     * call the Sphinx indexer on the given collection
     *
     * @param indexlist array           list of indexes to use
     */
    static function reindex($indexlist=null) {
        $indexes = '';
        if (is_array($indexlist)) {
            foreach ($indexlist as $ix) {
                $indexes .= ' ' . escapeshellarg($ix); // paranoia
            }
        }
        else
            $indexes = '--all';

        $cmd = sprintf('%s/indexer --config %s %s',
                        SPHINX_BINARY_DIR,
                        SPHINX_CONFIG_FILE,
                        $indexes);

        exec($cmd, $output, $ret);
        if ($ret !== 0) {
            trigger_error("Could not run Sphinx indexer!: ".join("\n", $output), E_USER_ERROR);
        }
    }

}
