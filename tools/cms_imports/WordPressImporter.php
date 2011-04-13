<?php
/*
    The data read / parsing is done by WordPress importer
    http://wordpress.org/extend/plugins/wordpress-importer/
    licensed under GPL2+, thus it should be ok to use it

    the data parser is from file
    http://svn.wp-plugins.org/wordpress-importer/trunk/parsers.php
    it is named as WordPressParsers.php herein

    the data importer goes along
    http://svn.wp-plugins.org/wordpress-importer/trunk/wordpress-importer.php
*/

require_once('WordPressParsers.php');

class WordPressImporter extends CMSImporterPlugin {

    // text authors
    var $authors = null;

    // auxiliary functions
    private function sanitize_user($p_userName, $p_specifier) {}
    private function esc_html($p_text) {}

    /**
     * Retrieve authors from parsed WXR data
     *
     * Uses the provided author information from WXR 1.1 files
     * or extracts info from each post for WXR 1.0 files
     *
     * @param array $import_data Data returned by a WXR parser
     */
    function get_authors_from_import( $import_data ) {
        if ( ! empty( $import_data['authors'] ) ) {
            $this->authors = $import_data['authors'];
        // no author information, grab it from the posts
        } else {
            foreach ( $import_data['posts'] as $post ) {
                $login = this->sanitize_user( $post['post_author'], true );
                if ( empty( $login ) ) {
                    printf( __( 'Failed to import author %s. Their posts will be attributed to the current user.', 'wordpress-importer' ), this->esc_html( $post['post_author'] ) );
                    echo '<br />';
                    continue;
                }

                if ( ! isset($this->authors[$login]) )
                    $this->authors[$login] = array(
                        'author_login' => $login,
                        'author_display_name' => $post['post_author']
                    );
            }
        }
    }

    /**
     * Makes the import from parsed data (by WXR_Parser) via the NewsMLCreator object
     *
     * @param NewsMLCreator $p_newsmlHolder the NewsML formatter
     * @param string $p_inputFileName input file name
     */
    public function makeImport($p_newsmlHolder, $p_inputFileName) {

        $parser = new WXR_Parser();
        $import_data = $parser->parse($p_inputFileName);

        $data_version = $import_data['version'];
        $this->get_authors_from_import($import_data);
        $data_posts = $import_data['posts'];
        $data_terms = $import_data['terms'];
        $data_categories = $import_data['categories'];
        $data_tags = $import_data['tags'];
        $data_base_url = esc_url($import_data['base_url']);



    }

}

?>
