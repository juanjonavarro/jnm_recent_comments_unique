<?php

$plugin['version'] = '0.5.2';
$plugin['author'] = 'Juanjo Navarro';
$plugin['author_uri'] = 'http://www.juanjonavarro.com/';
$plugin['description'] = 'List latest comments and commenters.';
$plugin['type'] = 0; 


if (!defined('txpinterface'))
    @include_once('zem_tpl.php');

if (0) {
?>
# --- BEGIN PLUGIN HELP ---

h1. Description:

<txp:jnm_recent_comments_unique /> is similar to txp:recent_comments. It displays the latest comments with the following changes:

* It only displays one link per article with the number of comments enclosed in parenthesis.
* It display the names of latest commenters (optional)

Options:

You can use the standard txp:recent_comments options (label, break, wraptag and limit). 
You can also use the following options:

* showcomments = Set to 'y' to display the commenters names. Default: "n"
* commbreak = String to separate the article's link from the commenters names. Default: "<br/>"
* commseparator = String to separate commenters names. Default: ", "
* commlimit = Display as much as this commenters. Default: 3
* commclass = If included, the commenters names are enclosed with a div with this class attribute.
* section = Restrict comments by this section. You can specify various sections separated by ",".
* autosection = Set to 'y' to autoselect comments based on the section being displayed. Default: "n"
* category = Restrict comments by this category. You can specify various categories separated by ",".
* autocategory = Set to 'y' to autoselect comments based on the category being displayed. Not valid inside an article page. Default: "n"
* linktoarticle = 'y'=article link links to article. 'n'=article link links to latest comment. Default: 'n'
* commentscount = Set to 'n' to not show comments count. Default: 'y'

Examples:
* <txp:jnm_recent_comments_unique break="li" />
Display the latest commented articles in an unordered list.
* <txp:jnm_recent_comments_unique break="li" showcomments="y" commlimit="2" commclass="commenters"/>
Display the latest commented articles with as much as two commenters. The commenters name are enclosed in a <div clas="commenters"> tag.

Author: *Juanjo Navarro*

# --- END PLUGIN HELP ---
<?php
}

# --- BEGIN PLUGIN CODE ---

function jnm_recent_comments_unique($atts)
{
  extract(lAtts(array(
    'label'    => '',
    'break'    => br,
    'wraptag'  => '',
    'limit'    => 10,
    'showcomments' => 'n',
    'commbreak'=> '<br/>',
    'commseparator' => ', ',
    'commlimit' => 3,
    'commclass' => '',
    'section' => '',
    'autosection' => 'n',
    'linktoarticle' => 'n',
    'category' => '',
    'autocategory' => '',
    'commentscount' => 'y',
    'class'    => __FUNCTION__
  ),$atts));

  $section = ($autosection=="y" && $GLOBALS['s']!='default') ? $GLOBALS['s'] : $section ;
  $category = ($autocategory=='y') ? $GLOBALS['c'] : $category;

  $q = "select parentid, max(concat('',comm.discussid)) lastcomm, name, max(comm.posted) jnm_date, count(*) ncomments "
            ."FROM ".PFX."txp_discuss comm "
            ."LEFT JOIN ".PFX."textpattern post ON comm.parentid = post.ID "
            ."WHERE visible=1 and status in (4,5)";
  if ($section!='') {
    $section = str_replace(",","|",$section);
    $section = str_replace(" ","", $section);
    $q.=" and post.section rlike '".doslash($section)."'";
  }
  if ($category!='') {
    $category = str_replace(",","|",$category);
    $category = str_replace(" ","", $category);
    $q.=" and (post.category1 rlike '".doslash($category)."' or post.category2 rlike '".doslash($category)."')";
  }
  $q .= " GROUP BY comm.parentid ORDER BY jnm_date DESC LIMIT 0 , $limit";
  $rs = safe_query($q);
  if ($rs) {
    if ($label) $out[] = $label;
    while ($a = nextRow($rs)) {
      extract($a);
      $Title = safe_field("Title",'textpattern',"ID=$parentid");
      if ($linktoarticle=='y') {
        $the_link=permlinkurl_id($parentid);
      } else {
        $the_link=permlinkurl_id($parentid).'#c'.$lastcomm;
      }
      $item = href($Title.($commentscount=='y'?' ('.$ncomments.')':''), $the_link);
      if ($showcomments=='y') {
        $rscomm = safe_rows_start('*','txp_discuss','visible=1 and parentid='.$parentid.' order by posted desc limit 0,'.($commlimit+1));
        if ($rscomm) {
          $commenters='';
          $ncomm=0;
          while ($b = nextRow($rscomm)) {
            if ($commenters <> '') {
              $commenters .= $commseparator;
            }
            if ($ncomm == $commlimit) {
              $commenters .= "...";
            } else {
              $commenters .= href($b['name'], permlinkurl_id($parentid).'#c'.$b['discussid']);
            }
            $ncomm++;
          }
          if ($commclass=='') {
            $item .= $commbreak.$commenters;
          }
          else {
            $item .= $commbreak.tag($commenters, 'div', ' class="'.$commclass.'"');
          }
        }
      }

      $out[] = $item;
    }
    if (!empty($out)) {
      return doWrap($out, $wraptag, $break, $class);
    }
  }
  return '';
}

# --- END PLUGIN CODE ---

?>
