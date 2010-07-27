<?
require_once "../phplib/util.php";
$dbResult = db_execute("select * from Definition where sourceId = 12 and status = 0 order by id");

$i = 0;
$modified = 0;
$ambiguousDefinitions = 0;
$ambiguities = 0;
while (!$dbResult->EOF) {
  $def = new Definition();
  $def->set($dbResult->fields);
  $ambiguousMatches = array();
  $newRep = text_internalizeDefinition($def->internalRep, $def->sourceId, $ambiguousMatches);
  if (count($ambiguousMatches) || ($newRep !== $def->internalRep)) {
    print "{$def->id} {$newRep}\n";
  }
  if ($newRep !== $def->internalRep) {
    $modified++;
    $def->internalRep = $newRep;
    $def->htmlRep = text_htmlize($newRep, $def->sourceId);
  }
  if (count($ambiguousMatches)) {
    $def->abbrevReview = ABBREV_AMBIGUOUS;
    $ambiguousDefinitions++;
    $ambiguities += count($ambiguousMatches);
    print "  AMBIGUOUS:";
    foreach ($ambiguousMatches as $match) {
      print " [{$match['abbrev']}]@{$match['position']}";
    }
    print "\n";
  } else {
    $def->abbrevReview = ABBREV_REVIEW_COMPLETE;
  }
  $def->save();
  $dbResult->MoveNext();
  $i++;
  if ($i % 1000 == 0) {
    print "$i definitions reprocessed, $modified modified, $ambiguousDefinitions ambiguous with $ambiguities ambiguities.\n";
  }
}
print "$i definitions reprocessed, $modified modified, $ambiguousDefinitions ambiguous with $ambiguities ambiguities.\n";

?>
