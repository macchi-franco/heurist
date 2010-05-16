<?php

// run_sql_query.php  - EXECUTES A DEFINED sql QUERY ON THE DATABASE AND PRINTS RESULTS
// This script is fairly generic and can be sued for any direct queries into the Heurist database
// It coudl be parameterised with a formatting string, but it was considered not worth the effort
// The first parameter indicates the formatting in the switch in print_row
// Ian Johnson and Steve White 25 Feb 2010

require_once('../php/modules/cred.php');

if (!is_logged_in()) {
	    header('Location: ' . BASE_PATH . 'login.php');
	    return;
        }
        
if (! is_admin()) {
    print "<html><body><p>You do not have sufficient privileges to access this page</p><p><a href=../php/login.php?logout=1>Log out</a></p></body></html>";
    return;   
}

require_once('../php/modules/db.php');
require_once('../legacy/.ht_stdefs');
?>

<html>

<head>
 <style type="text/css"> </style>
</head>

<body>

<base target="_blank">

<?php

// Deals with all the database connections stuff

    mysql_connection_db_select(DATABASE);

// Page headers to explain what the listing represents, includes query for backtraqcking

      switch ($_REQUEST['f']) {

      case 'ibblank':
        print "Internet Bookmarks with blank URLs";print "<p>"; print "Query: ";
        break;
      case 'garbage':
        print "Records with garbage URLs";print "<p>"; print "Query: ";
        break;
      case 'ibdupe':
        print "Internet Bookmarks with duplicate URLs (with other IBs)";print "<br>";
      print "Only the title of the first record is shown"; print "<p>"; print "Query: ";
        break;
      case 'alldupe':
      print "Records with duplicate URLs (with any rectype)";print "<br>";
      print "Only the title of the first record is shown"; print "<p>"; print "Query: ";
        Break;
      }

    $query = @$_REQUEST['q'];
    print $query; print "<p>";

    $res = mysql_query($query);
    while ($row = mysql_fetch_assoc($res)) {
    	print_row($row);
	}
?>

</body>
</html>

<?php

function print_row($row) {

// Prints a formatted representation of the data retreived for one row in the query
// Make sure that the query you passed in generates the fields you want to print
// Specify fields with $row[fieldname] or $row['fieldname'] (in most cases the quotes
// are unecessary although perhaps syntactically proper)


      switch ($_REQUEST['f']) {  // select the output formatting

      case 'ibblank': // Internet bookmark and blank URL
        print "<a href=http://heuristscholar.org/heurist/?q=ids:$row[rec_id]>$row[rec_id]</a>";
        print " :  ";print $row[rec_title];print " [ ";
        print "<a href=http://www.google.com/search?q=$row[rec_title]>Google</a>";
        print " ]<br>";
        break;
      case 'garbage': // Garbage in the URL
        print "<a href=http://heuristscholar.org/heurist/?q=ids:$row[rec_id]>$row[rec_id]</a>";
        print " :  $row[rec_title] <br>";
        print "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        print "url: $row[rec_url]";print "<br>";
        print "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        print "rec type: $row[rec_type]";print "<br>";
        break;
      case 'ibdupe': // Internet bookmark and duplicate URL with another IB
        print "<p>"; print $row[cnt]; print ": ";
        print "$row[rec_title]<br>";
        print "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        print "url: <a href=http://heuristscholar.org/heurist/?q=url:$row[rec_url]>$row[rec_url]</a>";
        print "<a href=http://www.google.com/search?q=url:$row[rec_url]>Google</a>";
                break;
      case 'alldupe': // Duplicate URL with another record (all record types)
        print "<p>"; print $row[cnt]; print ": ";
        print $row[rec_title]; print "<br>";
        print "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        print "url: <a href=http://heuristscholar.org/heurist/?q=url:$row[rec_url]>$row[rec_url]</a> [ ";
        print "<a href=http://www.google.com/search?q=url:$row[rec_url]>Google</a> ]";
               break;
      } // end Switch


} // end function print_row
                  

?>
