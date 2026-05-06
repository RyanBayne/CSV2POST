<?php

namespace CSV2POST\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class WizardFlow {

    public function get_setup_steps() {
        $menu = $this->menu_array();
        $steps = array();
        $step_names = array( 'setupcsv', 'setupproject', 'setuprules', 'setupimport', 'setupdesign', 'setupposts' );

        foreach ( $step_names as $step_name ) {
            if ( ! isset( $menu[ $step_name ] ) ) {
                continue;
            }

            $step = $menu[ $step_name ];

            $steps[] = array(
                'slug' => $step['slug'],
                'name' => $step['name'],
                'label' => $step['pluginmenu'],
                'title' => $step['title'],
                'group' => $step['groupname'],
                'parent' => $step['parent'],
            );
        }

        return $steps;
    }

    private function menu_array() {
        if ( ! class_exists( '\CSV2POST_TabMenu' ) ) {
            require_once CSV2POST_ABSPATH . 'classes/class-pluginmenu.php';
        }

        $tab_menu = new \CSV2POST_TabMenu();

        return $tab_menu->menu_array();
    }
}
