<?php
declare(strict_types=1);
?>
<div class="post_box">

<h2>AI Dashboard</h2>

<table width="100%">

<tr>

<td>Generated Today</td>

<td><?= $stats['generated_today']; ?></td>

</tr>

<tr>

<td>Published Today</td>

<td><?= $stats['published_today']; ?></td>

</tr>

<tr>

<td>Pending Queue</td>

<td><?= $stats['queue_pending']; ?></td>

</tr>

<tr>

<td>Running Queue</td>

<td><?= $stats['queue_running']; ?></td>

</tr>

<tr>

<td>Failed Jobs</td>

<td><?= $stats['queue_failed']; ?></td>

</tr>

<tr>

<td>Prompt Templates</td>

<td><?= $stats['prompts']; ?></td>

</tr>

<tr>

<td>Workflows</td>

<td><?= $stats['workflows']; ?></td>

</tr>

</table>

</div>

<div class="post_box">

<h2>Quick Actions</h2>

<p>

<a href="article_new.php" class="more">New Article</a>

<a href="articles.php" class="more">Articles</a>

<a href="queue.php" class="more">Queue</a>

<a href="prompts.php" class="more">Prompts</a>

<a href="workflows.php" class="more">Workflows</a>

<a href="settings.php" class="more">Settings</a>

</p>

</div>