<?php
/* ---------------------------------------------------

  1st query : get all custom-field names
  2nd query : get orphaned parent entries (field #ID)
  3rd query : get or delete orphaned parent and child
  4th query : get empty child entries (value)
  5th query : get or delete empty parent and child

--------------------------------------------------- */

/* Exit if accessed directly */
global $acf_clean_1, $acf_clean_2, $acf_db_prefix, $acf_prefix;
if (!defined('ABSPATH')) {
    exit;
}

/* Only parse code when included by parent */
if (!defined('CF_CLEANER')) {
    die();
}

/* Define the action (select | delete) */
define('ACF_ACTION_1', $acf_clean_1 ? 'DELETE' : 'SELECT *');
define('ACF_ACTION_2', $acf_clean_2 ? 'DELETE' : 'SELECT *');

/* Some variables */
$err = false;
$msg = false;
$txt = false;
$inf = '';
$cnt_1 = 0;
$cnt_2 = 0;
$cnt_fields = 0;
$cnt_values = 0;
$acf_empty = 0;
$acf_orphan = 0;
$sql = 0;

$inf .= '<h3>Database : ' . DB_NAME . ' // <span id="acf_total">0</span> ACF-records</h3>';
$inf .= '<b><span id="acf_cnt">0</span> entries</b> found (in <span id="acf_fields">0</span> ACF-fields / <span id="acf_values">0</span> ACF-values), after performing <a onclick="(function($){try{$(\'#acf_help\').toggle();}catch(e){}}(jQuery));" style="text-decoration:underline;cursor:pointer;" title="query info"><span id="acf_sql">0</span>#5 queries</a>.';
$inf .= '<p><ol id="acf_help" style="display:none;">';
$inf .= '<li>Get all ACF-field names.</li>';
$inf .= '<li>Get <b>orphaned</b> parent entries (<i>field ID</i>).</li>';
$inf .= '<li>Select / delete orphaned parent and child.</li>';
$inf .= '<li>Get <b>empty</b> child entries (<i>value</i>).</li>';
$inf .= '<li>Select / delete empty parent and child.</li>';
$inf .= '</ol></p><hr>';

_e($inf, 'cf_cleaner');

/* --------------------------------------------------- */
/* Open the connection to the database */
/* --------------------------------------------------- */
$connection = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

if ($connection->connect_error) {
    $err .= '<b>Error :</b> unable to connect to database [<b>' . $connection->connect_error . '</b>].<br>';
}

/* --------------------------------------------------- */
/* --------------------------------------------------- */
/* 1#3 - Save all available ACF-fields to an array */
/* --------------------------------------------------- */
/* --------------------------------------------------- */
$query = 'SELECT DISTINCT `meta_key` FROM `' . $acf_db_prefix . 'postmeta`';

$result = $array_fields = null;

if (!$result = $connection->query($query)) {
    $err .= '<b>Error :</b> there was an error running the <b>1st</b> query [<b>' . $connection->error . '</b>].<br>';
}

/* Fields found, save them */
if ($result) {
    $sql++;
    $array_fields = array();

    while ($row = $result->fetch_assoc()) {
        $array_fields[] = $row['meta_key'];
    }

    mysqli_free_result($result);
} else {
    /* No ACF-fields found at all */
    $msg .= 'No entries found during the <b>1st</b> query [<b>Fieldnames</b>].<br>';
}

$cnt_fields = $array_fields ? count($array_fields) : 0;

/* --------------------------------------------------- */
/* --------------------------------------------------- */
/* 2#3 - Get all orphaned ACF-field entries */
/* --------------------------------------------------- */
/* --------------------------------------------------- */
$query = 'SELECT `meta_id` FROM `' . $acf_db_prefix . 'postmeta` pm LEFT JOIN ' . $acf_db_prefix . 'posts wp ON wp.ID = pm.post_id WHERE wp.ID IS NULL';
$result = null;

if ($cnt_fields > 0) {
    if (!$result = $connection->query($query)) {
        $err .= '<b>Error :</b> there was an error running the <b>2nd</b> query [<b>' . $connection->error . '</b>].<br>';
    }
}

/* Orphaned entries are existing */
if ($result) {
    $sql++;
    $array_fields = array();

    /* Save orphaned entry to an array */
    while ($row = $result->fetch_assoc()) {
        $array_fields[] = $row['meta_id'];
        $cnt_1 += 1;
    }

    mysqli_free_result($result);
} else {
    /* No orphaned entries found */
    $msg .= 'No entries found during the <b>2nd</b> query [<b>orphaned parent #ID\'s</b>].<br>';
}

/* --------------------------------------------------- */
/* Select or delete ORPHANED entries (parent & child) */
/* --------------------------------------------------- */
$query = ACF_ACTION_1 . ' FROM `' . $acf_db_prefix . 'postmeta` WHERE `meta_id` IN (' . implode(',', $array_fields ?? array()) . ')';
$result = null;

if (count($array_fields ?? array())) {
    if (!$result = $connection->query($query)) {
        $err .= '<b>Error :</b> there was an error running the <b>3rd</b> query [<b>' . $connection->error . '</b>].<br>';
    }
}

_e('<h4>ORPHANED ENTRIES <span id="acf_orphan" style="color:#888;">#0</span></h4>', 'cf_cleaner');
echo '<table class="widefat fixed striped">';
echo '<thead><tr><td>Entry</td><td>Type</td><td><code>meta_id</code></td><td><code>post_id</code></td><td><code>meta_key</code></td><td><code>meta_value</code></td></tr></thead>';
echo '<tfoot><tr><td>Entry</td><td>Type</td><td><code>meta_id</code></td><td><code>post_id</code></td><td><code>meta_key</code></td><td><code>meta_value</code></td></tr></tfoot><tbody>';

/* When entries are available, iterate onthem */
if ($result && !$acf_clean_1) {
    $sql++;
    $i = 0;

    while ($row = $result->fetch_assoc()) {
        $i++;
        $acf_orphan++;

        echo '<tr>';
        echo '<td>' . sprintf('%03d', $i) . '</td>';
        echo '<td><code>orphan</code></td>';
        echo '<td>' . $row['meta_id'] . '</td>';
        edit_post_link( $row['post_id'], '<td>', '</td>', $row['post_id']);
        echo '<td>' . $row['meta_key'] . '</td>';
        echo '<td>' . htmlentities($row['meta_value']) . '</td>';
        echo '</tr>';
    }

    mysqli_free_result($result);
} else {
    /* No entries are discovered */
    $msg .= 'No entries found during the <b>3rd</b> query [<b>orphaned entries</b>].<br>';
    _e('<tr><td colspan="6" class="notice notice-success">No <b>orphaned</b> entries found.</td></tr>', 'cf_cleaner');

}

echo '</tbody></table>';

/* --------------------------------------------------- */
/* --------------------------------------------------- */
/* 3#3 - Get all empty ACF-field entries */
/* --------------------------------------------------- */
/* --------------------------------------------------- */
$query = 'SELECT `meta_id` FROM `' . $acf_db_prefix . 'postmeta` WHERE upper(`meta_key`) LIKE "' . strtoupper($acf_prefix) . '%" AND `meta_value` = ""';
$result = null;

if (!$result = $connection->query($query)) {
    $err .= '<b>Error :</b> there was an error running the <b>4th</b> query [<b>' . $connection->error . '</b>].<br>';
}

/* When empty entries are encountered */
if ($result) {
    $sql++;
    $array_values = array();

    while ($row = $result->fetch_assoc()) {
        /* Don't add orphaned, empty records */
        if (!in_array($row['meta_id'], $array_fields)) {
            $array_values[] = $row['meta_id'];
            $cnt_2 += 1;
        }
    }

    mysqli_free_result($result);

    /* --------------------------------------------------- */
    /* Select or delete EMPTY entries (parent & child) */
    /* --------------------------------------------------- */
    $query = ACF_ACTION_2 . ' FROM `' . $acf_db_prefix . 'postmeta` WHERE `meta_id` IN (' . implode(',', $array_values) . ')';
    $result = null;

    if (count($array_values)) {
        if (!$result = $connection->query($query)) {
            $err .= '<b>Error :</b> there was an error running the <b>5th</b> query [<b>' . $connection->error . '</b>].<br>';
        }
    }

    _e('<h4>EMPTY ENTRIES <span id="acf_empty" style="color:#888;">#0</span></h4>', 'cf_cleaner');
    echo '<table class="widefat fixed striped">';
    echo '<thead><tr><td>Entry</td><td>Type</td><td><code>meta_id</code></td><td><code>post_id</code></td><td><code>meta_key</code></td><td><code>meta_value</code></td></tr></thead>';
    echo '<tfoot><tr><td>Entry</td><td>Type</td><td><code>meta_id</code></td><td><code>post_id</code></td><td><code>meta_key</code></td><td><code>meta_value</code></td></tr></tfoot><tbody>';

    /* When entries are found, iterate them */
    if ($result && !$acf_clean_2) {
        $sql++;
        $i = 0;

        while ($row = $result->fetch_assoc()) {
            $i++;
            $acf_empty++;
            echo '<tr>';
            echo '<td>' . sprintf('%03d', $i) . '</td>';
            echo '<td><code>empty</code></td>';
            echo '<td>' . $row['meta_id'] . '</td>';
            edit_post_link( $row['post_id'], '<td>', '</td>', $row['post_id']);
            echo '<td>' . $row['meta_key'] . '</td>';
            echo '<td>' . htmlentities($row['meta_value']) . '</td>';
            echo '</tr>';
        }

        mysqli_free_result($result);

    } else {
        $msg .= 'No entries found during the <b>4th</b> query [<b>empty entries</b>].<br>';
        _e('<tr><td colspan="6" class="notice notice-success">No <b>empty</b> entries found.</td></tr>', 'cf_cleaner');
    }

    echo '</tbody></table>';

}

/* --------------------------------------------------- */
/* All queries are done, show the results */
/* --------------------------------------------------- */
if ($msg || $err) {
    /* Error is more important than message */
    if ($err) {
        $txt = $err;
        $class = 'error';
    } else {
        $txt = $msg;
        $class = 'info';
    }
    ?>

    <div class="notice notice-<?php echo $class; ?> is-dismissible" style="padding:10px 12px;">
        <?php _e($txt, 'cf_cleaner'); ?>
    </div>

    <?php
}

$query = 'SELECT `meta_id` FROM `' . $acf_db_prefix . 'postmeta` WHERE upper(`meta_key`) LIKE "%' . strtoupper($acf_prefix) . '%"';
$result = null;

if ($result) {
    $cnt_values = mysqli_num_rows($result);
    mysqli_free_result($result);
}

/* Get final result [what is not-cleaned | left] */

$acf_total = $cnt_1 + $cnt_2;

if ($acf_clean_1) {
    $acf_total -= $cnt_1;
}

if ($acf_clean_2) {
    $acf_total -= $cnt_2;
}

/* Close the connection */

mysqli_close($connection);

/* Update the totals-count, without object-buffering */

echo '<script>(function($) {

          try {
            $("#acf_cnt").text("' . $acf_total . '");
            $("#acf_orphan").text("#' . $acf_orphan . '");
            $("#acf_empty").text("#' . $acf_empty . '");
            $("#acf_fields").text("' . $cnt_fields . '");
            $("#acf_values").text("' . $cnt_values . '");
            $("#acf_total").text("' . ($cnt_fields + intval($cnt_values)) . '");
            $("#acf_sql").text("' . $sql . '");
          } catch(e) {
            /* TODO */
          }
        } (jQuery));
  </script>';

if ($acf_clean_1 || $acf_clean_2) {
    /* Show final result, on top of page */
    ?>

    <div class="notice notice-success is-dismissible hidden" id="acf_succeed">
        <p>
            <?php _e('<b>Succeeded</b> : ACF Cleaner removed <b><span>0</span> entries</b>.', 'cf_cleaner'); ?>
        </p>
    </div>

    <?php
    if (!$err) {
        /* Get final result [what is cleaned] */
        $acf_total = 0;

        if ($acf_clean_1) {
            $acf_total += $cnt_1;
        }

        if ($acf_clean_2) {
            $acf_total += $cnt_2;
        }

        echo '<script>(function($){try{$("#acf_succeed span").text("' . $acf_total . '");$("#acf_succeed").removeClass("hidden");}catch(e){}}(jQuery));</script>';
    }
}
