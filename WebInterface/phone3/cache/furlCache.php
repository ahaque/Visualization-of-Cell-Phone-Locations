<?php
/**
 * FURL Templates cache. Do not attempt to modify this file.
 * Please modify the relevant 'furlTemplates.php' file in /{app}/extensions/furlTemplates.php
 * and rebuild from the Admin CP
 *
 * Written: Sun, 06 Sep 2009 01:36:56 +0000
 *
 * Why? Because Matt says so.
 */
 $templates = array (
  '__data__' => 
  array (
    'start' => '-',
    'end' => '/',
    'varBlock' => '/page__',
    'varSep' => '__',
  ),
  'section=register' => 
  array (
    'app' => 'core',
    'allowRedirect' => 0,
    'out' => 
    array (
      0 => '#app=core(&amp;|&)module=global(&amp;|&)section=register(&amp;|&|$)#i',
      1 => 'register/$3',
    ),
    'in' => 
    array (
      'regex' => '#/register/?(.+?)?$#i',
      'matches' => 
      array (
        0 => 
        array (
          0 => 'app',
          1 => 'core',
        ),
        1 => 
        array (
          0 => 'module',
          1 => 'global',
        ),
        2 => 
        array (
          0 => 'section',
          1 => 'register',
        ),
      ),
    ),
  ),
  'section=rss' => 
  array (
    'app' => 'core',
    'allowRedirect' => 0,
    'out' => 
    array (
      0 => '#app=core(&amp;|&)module=global(&amp;|&)section=rss(&amp;|&)type=(\\w+?)$#i',
      1 => 'rss/$4/',
    ),
    'in' => 
    array (
      'regex' => '#/rss/(\\w+?)/$#i',
      'matches' => 
      array (
        0 => 
        array (
          0 => 'app',
          1 => 'core',
        ),
        1 => 
        array (
          0 => 'module',
          1 => 'global',
        ),
        2 => 
        array (
          0 => 'section',
          1 => 'rss',
        ),
        3 => 
        array (
          0 => 'type',
          1 => '$1',
        ),
      ),
    ),
  ),
  'section=rss2' => 
  array (
    'app' => 'core',
    'allowRedirect' => 0,
    'out' => 
    array (
      0 => '#app=core(&amp;|&)module=global(&amp;|&)section=rss(&amp;|&)type=(\\w+?)(&amp;|&)id=(\\d+?)$#i',
      1 => 'rss/$4/$6-#{__title__}/',
    ),
    'in' => 
    array (
      'regex' => '#/rss/(\\w+?)/(\\d+?)-#i',
      'matches' => 
      array (
        0 => 
        array (
          0 => 'app',
          1 => 'core',
        ),
        1 => 
        array (
          0 => 'module',
          1 => 'global',
        ),
        2 => 
        array (
          0 => 'section',
          1 => 'rss',
        ),
        3 => 
        array (
          0 => 'type',
          1 => '$1',
        ),
        4 => 
        array (
          0 => 'id',
          1 => '$2',
        ),
      ),
    ),
  ),
  'showannouncement' => 
  array (
    'app' => 'forums',
    'allowRedirect' => 1,
    'out' => 
    array (
      0 => '#showannouncement=(.+?)((?:&|&amp;)f=(.+?))?(&|$)#i',
      1 => 'forum-$3/announcement-$1-#{__title__}/$4',
    ),
    'in' => 
    array (
      'regex' => '#/forum-(\\d+?)?/announcement-(\\d+?)-#i',
      'matches' => 
      array (
        0 => 
        array (
          0 => 'showannouncement',
          1 => '$2',
        ),
        1 => 
        array (
          0 => 'f',
          1 => '$1',
        ),
      ),
    ),
  ),
  'showforum' => 
  array (
    'app' => 'forums',
    'allowRedirect' => 1,
    'out' => 
    array (
      0 => '#showforum=(.+?)(&|$)#i',
      1 => 'forum/$1-#{__title__}/$2',
    ),
    'in' => 
    array (
      'regex' => '#/forum/(\\d+?)-#i',
      'matches' => 
      array (
        0 => 
        array (
          0 => 'showforum',
          1 => '$1',
        ),
      ),
    ),
  ),
  'showtopic' => 
  array (
    'app' => 'forums',
    'allowRedirect' => 1,
    'out' => 
    array (
      0 => '#showtopic=(.+?)(&|$)#i',
      1 => 'topic/$1-#{__title__}/$2',
    ),
    'in' => 
    array (
      'regex' => '#/topic/(\\d+?)-#i',
      'matches' => 
      array (
        0 => 
        array (
          0 => 'showtopic',
          1 => '$1',
        ),
      ),
    ),
  ),
  'act=idx' => 
  array (
    'app' => 'forums',
    'allowRedirect' => 0,
    'out' => 
    array (
      0 => '#act=idx(&|$)#i',
      1 => 'index',
    ),
    'in' => 
    array (
      'regex' => '#/index$#i',
      'matches' => 
      array (
        0 => 
        array (
          0 => 'act',
          1 => 'idx',
        ),
      ),
    ),
  ),
  'showuser' => 
  array (
    'app' => 'members',
    'allowRedirect' => 1,
    'out' => 
    array (
      0 => '#showuser=(.+?)(&|$)#i',
      1 => 'user/$1-#{__title__}/$2',
    ),
    'in' => 
    array (
      'regex' => '#/user/(\\d+?)-#i',
      'matches' => 
      array (
        0 => 
        array (
          0 => 'showuser',
          1 => '$1',
        ),
      ),
    ),
  ),
  'cal_week' => 
  array (
    'app' => 'calendar',
    'allowRedirect' => 1,
    'out' => 
    array (
      0 => '#app=calendar(?:&|&amp;)module=calendar(?:&|&amp;)cal_id=(\\d+?)(?:&|&amp;)do=showweek(?:&|&amp;)week=(\\d+?)(?:&|$)#i',
      1 => 'calendar/\\1/week-\\2',
    ),
    'in' => 
    array (
      'regex' => '#/calendar/(\\d+?)/week-(\\d+?)(/|$)#i',
      'matches' => 
      array (
        0 => 
        array (
          0 => 'app',
          1 => 'calendar',
        ),
        1 => 
        array (
          0 => 'module',
          1 => 'calendar',
        ),
        2 => 
        array (
          0 => 'do',
          1 => 'showweek',
        ),
        3 => 
        array (
          0 => 'cal_id',
          1 => '$1',
        ),
        4 => 
        array (
          0 => 'week',
          1 => '$2',
        ),
      ),
    ),
  ),
  'event' => 
  array (
    'app' => 'calendar',
    'allowRedirect' => 1,
    'out' => 
    array (
      0 => '#app=calendar(?:&|&amp;)module=calendar(?:&|&amp;)cal_id=(\\d+?)(?:&|&amp;)do=showevent(?:&|&amp;)event_id=(\\d+?)(?:&|$)#i',
      1 => 'calendar/\\1/event-\\2',
    ),
    'in' => 
    array (
      'regex' => '#/calendar/(\\d+?)/event-(\\d+?)(/|$)#i',
      'matches' => 
      array (
        0 => 
        array (
          0 => 'app',
          1 => 'calendar',
        ),
        1 => 
        array (
          0 => 'module',
          1 => 'calendar',
        ),
        2 => 
        array (
          0 => 'do',
          1 => 'showevent',
        ),
        3 => 
        array (
          0 => 'cal_id',
          1 => '$1',
        ),
        4 => 
        array (
          0 => 'event_id',
          1 => '$2',
        ),
      ),
    ),
  ),
  'cal_day' => 
  array (
    'app' => 'calendar',
    'allowRedirect' => 1,
    'out' => 
    array (
      0 => '#app=calendar(?:&|&amp;)module=calendar(?:&|&amp;)cal_id=(.+?)(?:&|&amp;)do=showday(?:&|&amp;)y=(.+?)&amp;m=(.+?)&amp;d=(.+?)(?:&|$)#i',
      1 => 'calendar/\\1/day-\\2-\\3-\\4',
    ),
    'in' => 
    array (
      'regex' => '#/calendar/(\\d+?)/day-(\\d+?)-(\\d+?)-(\\d+?)(/|$)#i',
      'matches' => 
      array (
        0 => 
        array (
          0 => 'app',
          1 => 'calendar',
        ),
        1 => 
        array (
          0 => 'module',
          1 => 'calendar',
        ),
        2 => 
        array (
          0 => 'do',
          1 => 'showday',
        ),
        3 => 
        array (
          0 => 'cal_id',
          1 => '$1',
        ),
        4 => 
        array (
          0 => 'y',
          1 => '$2',
        ),
        5 => 
        array (
          0 => 'm',
          1 => '$3',
        ),
        6 => 
        array (
          0 => 'd',
          1 => '$4',
        ),
      ),
    ),
  ),
  'app=calendar' => 
  array (
    'app' => 'calendar',
    'allowRedirect' => 1,
    'out' => 
    array (
      0 => '#app=calendar$#i',
      1 => 'calendar/',
    ),
    'in' => 
    array (
      'regex' => '#/calendar/?$#i',
      'matches' => 
      array (
        0 => 
        array (
          0 => 'app',
          1 => 'calendar',
        ),
      ),
    ),
  ),
  'page' => 
  array (
    'app' => 'ccs',
    'allowRedirect' => 1,
    'out' => 
    array (
      0 => '#app=ccs(?:&amp;|&)module=pages(?:&amp;|&)section=pages(?:&amp;|&)(?:folder=(.*?)(?:&amp;|&))(?:id|page)=(.+?)(&|$)#i',
      1 => 'page/$1/#{__title__}',
    ),
    'in' => 
    array (
      'regex' => '#/page/(.*?)/(.+?)$#i',
      'matches' => 
      array (
        0 => 
        array (
          0 => 'app',
          1 => 'ccs',
        ),
        1 => 
        array (
          0 => 'module',
          1 => 'pages',
        ),
        2 => 
        array (
          0 => 'section',
          1 => 'pages',
        ),
        3 => 
        array (
          0 => 'folder',
          1 => '$1',
        ),
        4 => 
        array (
          0 => 'page',
          1 => '$2',
        ),
      ),
    ),
  ),
);

?>