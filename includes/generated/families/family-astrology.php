<?php
/**
 * Generated from the api.astroway.info OpenAPI spec. Do not edit.
 * Run scripts/generate-from-openapi.py instead.
 *
 * @package AstroWay\WPPlugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return [
	'astroway_family_genogram' => [ '/family/genogram', 'POST', [ [ 'grandparent', '', 'j', 3 ], [ 'parent', '', 'j', 3 ], [ 'child', '', 'j', 3 ] ] ],
	'astroway_family_parent_child_deep' => [ '/family/parent-child-deep', 'POST', [ [ 'parent', '', 'j', 3 ], [ 'child', '', 'j', 3 ] ] ],
	'astroway_family_saturn_return_cycles' => [ '/family/saturn-return-cycles', 'POST', [ [ 'members', '', 'j', 3 ] ] ],
	'astroway_family_sibling_dynamics' => [ '/family/sibling-dynamics', 'POST', [ [ 'sibling1', '', 'j', 3 ], [ 'sibling2', '', 'j', 3 ], [ 'parent', '', 'j', 2 ] ] ],
	'astroway_family_system_pattern' => [ '/family/system-pattern', 'POST', [ [ 'members', '', 'j', 3 ] ] ],
];
