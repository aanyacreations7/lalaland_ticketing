Brand fonts (Woodburger and Antique Olive, as used on lalalandflea.com) are
licensed to La La Land and are NOT stored in this public repository.

You don't need to upload anything: the first visitor's browser asks
/font.php, which downloads each font once from the main site
(main_site_url in app/config.php), saves it in this folder and serves
the cached copy from then on.

If the main site ever moves the files, either upload them here yourself
(same file names) or set 'brand_fonts' in app/config.php, e.g.
  'brand_fonts' => [
      'woodburger' => ['/wp-content/uploads/2026/01/WoodBurger.ttf', 'VLNL-WoodBurger-W00-Regular.ttf'],
  ],
