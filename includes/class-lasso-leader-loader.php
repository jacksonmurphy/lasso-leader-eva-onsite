<?php
/**
 * Version: 4.0.43
 */
// Prevent direct access to the file
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The plugin loader class.
 *
 * Orchestrates the hooks of the plugin.
 *
 * @since    1.0.0
 */
class Lasso_Leader_Loader {

    /**
     * The array of actions registered with WordPress.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $actions    The actions registered with WordPress to fire when the plugin loads.
     */
    protected $actions;

    /**
     * The array of filters registered with WordPress.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $filters    The filters registered with WordPress to fire when the plugin loads.
     */
    protected $filters;

    /**
     * Initialize the collections used to maintain the actions and filters.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->actions = array();
        $this->filters = array();
    }

    /**
     * Add a new action to the collection to be registered with WordPress.
     *
     * @since    1.0.0
     * @param    string  $hook             The name of the WordPress action that is being registered.
     * @param    object  $component        A reference to the instance of the object on which the action is defined.
     * @param    string  $callback         The name of the function that would be called in the component.
     * @param    int     $priority         Optional. The priority at which the function should be fired. Default is 10.
     * @param    int     $accepted_args    Optional. The number of arguments that should be passed to the callback. Default is 1.
     */
    public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
        $this->actions = $this->add( $this->actions, (string)$hook, $component, (string)$callback, (int)$priority, (int)$accepted_args ); // Ensure types
    }

    /**
     * Add a new filter to the collection to be registered with WordPress.
     *
     * @since    1.0.0
     * @param    string  $hook             The name of the WordPress filter that is being registered.
     * @param    object  $component        A reference to the instance of the object on which the filter is defined.
     * @param    string  $callback         The name of the function that would be called in the component.
     * @param    int     $priority         Optional. The priority at which the function should be fired. Default is 10.
     * @param    int     $accepted_args    Optional. The number of arguments that should be passed to the callback. Default is 1
     */
    public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
        $this->filters = $this->add( $this->filters, (string)$hook, $component, (string)$callback, (int)$priority, (int)$accepted_args ); // Ensure types
    }

    /**
     * A utility function that is used to register the actions and hooks into a single
     * collection.
     *
     * @since    1.0.0
     * @access   private
     * @param    array     $hooks            The collection of hooks that is being registered (actions or filters).
     * @param    string    $hook             The name of the WordPress hook that is being registered.
     * @param    object    $component        A reference to the instance of the object on which the hook is defined.
     * @param    string    $callback         The name of the function that would be called in the component.
     * @param    int       $priority         The priority at which the function should be fired.
     * @param    int       $accepted_args    The number of arguments that should be passed to the callback.
     * @return   array                            The collection of hooks that have been been registered with WordPress.
     */
    private function add( $hooks, $hook, $component, $callback, $priority, $accepted_args ) {
        $hooks[] = array(
            'hook'          => (string)$hook, // Ensure string
            'component'     => $component,
            'callback'      => (string)$callback, // Ensure string
            'priority'      => (int)$priority, // Ensure int
            'accepted_args' => (int)$accepted_args // Ensure int
        );

        return $hooks;
    }

    /**
     * Register the filters and actions with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        foreach ( $this->filters as $hook ) {
            add_filter( (string)$hook['hook'], array( $hook['component'], (string)$hook['callback'] ), (int)$hook['priority'], (int)$hook['accepted_args'] ); // Ensure types
        }

        foreach ( $this->actions as $hook ) {
            add_action( (string)$hook['hook'], array( $hook['component'], (string)$hook['callback'] ), (int)$hook['priority'], (int)$hook['accepted_args'] ); // Ensure types
        }
    }
}