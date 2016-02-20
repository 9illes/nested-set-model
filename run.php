<?php
include_once __DIR__.'/bootstrap.php';

use Halstack\NestedCategory as Category;
use Halstack\NestedCategoryDAO as CategoryDAO;

function usage()
{
    $cmd = $_SERVER['argv'][0];
    echo "usage :\n";
    echo "\n";
    echo "Append a child to the category with this [id] (defining an optional name) :\n";
    echo "\tphp $cmd -a -r [id] {-n=my_name}\n";
    echo "Insert a child to the left of a category (defining an optional name) :\n";
    echo "\tphp $cmd -i -r [id] {-n=my_name}\n";
    echo "\n";
    echo "options :\n";
    echo "-a append a child category (require -r)\n";
    echo "-i insert a category to the left of a category (require -r)\n";
    echo "-r (int) id of referenced category\n";
    echo "-n=(string) optional category name\n\n";
}

$options = getopt("aidr:tn::");

// start

$dao = new CategoryDAO($conn);
$dao->initDatabaseModel();

$appendAction = isset($options['a']);
$insertAction = isset($options['i']);
$deleteAction = isset($options['d']);

if ($appendAction || $insertAction) {

    if (!empty($options['r']) && is_numeric($options['r'])) {
        $ref = $dao->findById($options['r']);

        $child = new Category;
        $child->name = empty($options['n']) ? uniqid('cat-') : $options['n'];

        if ($appendAction) {
            $dao->append($ref, $child);
        } else {
            $dao->insert($ref, $child);
        }
    } else {
        echo "Error : missing parameter : -r int\n\n";
        usage();
    }
} else if ($deleteAction) {
    $ref = $dao->findById($options['r']);
    $dao->deleteSubtree($ref);

} else {
    usage();
}
echo "Tree :\n\n";

$tree = $dao->getTree();
foreach($tree as $row) {
    echo str_repeat('  ', $row->depth).$row->name." (".$row->id.")"."\n";
}
