<?php

namespace modules\demo\console\controllers;

use Craft;
use craft\console\Controller;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\elements\User;
use craft\helpers\Console;
use DateTime;
use yii\console\ExitCode;

/**
 * The Dog-ear Books catalogue and journal, with writers, for the social
 * images to show. The covers and journal pictures are in web/uploads. Run
 * it again to put everything back.
 */
class ContentController extends Controller
{
    public $defaultAction = 'index';

    protected const WRITERS = [
        'mira' => 'Mira Castellane',
        'june' => 'June Okonkwo',
        'tobias' => 'Tobias Venn',
    ];

    protected const BOOKS = [
        ['the-salt-orchard', 'The Salt Orchard', 'Imogen Hale', '14.00', 'Three sisters inherit an orchard that grows only in sea air.'],
        ['coral-arithmetic', 'Coral Arithmetic', 'Dev Okafor', '13.00', 'A marine biologist counts what’s left of a reef, and what isn’t.'],
        ['ninety-blue-kites', 'Ninety Blue Kites', 'Sora Lindqvist', '9.99', 'A village flies a kite for every child who left. One comes back.'],
        ['the-paper-cartographer', 'The Paper Cartographer', 'Agnes Moreau', '16.50', 'Maps of places that don’t exist yet, drawn by a girl who can’t leave home.'],
        ['moss-on-the-northern-wall', 'Moss on the Northern Wall', 'Callum Reid', '12.00', 'Poems from a year spent watching one wall in the Highlands.'],
        ['the-lantern-ledger', 'The Lantern Ledger', 'Priya Natarajan', '15.00', 'An accountant in 1890s Bombay finds a ledger that balances itself.'],
        ['small-weathers', 'Small Weathers', 'Lena Fischer', '10.50', 'Stories no longer than a rain shower, collected over ten years.'],
        ['under-the-copper-roof', 'Under the Copper Roof', 'Tomás Ibarra', '17.00', 'A family saga told by the house they lived in.'],
        ['seven-ways-to-boil-the-sea', 'Seven Ways to Boil the Sea', 'Hana Sato', '13.50', 'A cook’s memoir of the ships she fed and the storms they met.'],
        ['notes-from-a-lighthouse', 'Notes from a Lighthouse', 'Owen Pryce', '11.00', 'The last keeper of a Welsh light writes to whoever comes next.'],
        ['the-quiet-apprentice', 'The Quiet Apprentice', 'Mei Chen', '12.50', 'A clockmaker’s apprentice who never speaks hears everything.'],
        ['a-field-guide-to-leaving', 'A Field Guide to Leaving', 'Ruth Adeyemi', '14.50', 'Birds, trains and the people who take them, catalogued with care.'],
    ];

    protected const JOURNAL = [
        ['autumn-reading-list', 'Our autumn reading list: twelve books for longer evenings', 'mira', '2026-10-02', 'Short days, long books. The shop’s favourites for the season, from sea sagas to small poems.'],
        ['in-conversation-with-imogen-hale', 'In conversation with Imogen Hale', 'tobias', '2026-09-18', 'The author of The Salt Orchard on sea air, sisters and why every orchard is a kind of argument.'],
        ['why-we-dog-ear', 'Why we dog-ear (and why you should too)', 'june', '2026-09-03', 'A defence of folding the corner, from the shop that named itself after it.'],
        ['the-shop-cat-has-opinions', 'The shop cat has opinions', 'mira', '2026-08-21', 'Biscuit’s five-star picks, chosen by where she chooses to sleep.'],
        ['making-the-paper-cartographer-cover', 'Making the cover of The Paper Cartographer', 'tobias', '2026-07-30', 'How a hand-drawn map became the year’s most photographed book jacket.'],
        ['events', 'Events', 'june', '2026-07-01', null],
    ];

    /**
     * Makes the demo shop's books, journal and writers (dev only).
     */
    public function actionIndex(): int
    {
        if (! Craft::$app->getConfig()->getGeneral()->devMode) {
            $this->stderr("demo/content replaces the demo's books and journal, so it only runs with devMode on.\n", Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        Craft::$app->runAction('index-assets/one', ['images', 'interactive' => false]);

        $writers = [];

        foreach (self::WRITERS as $username => $name) {
            $user = User::find()->username($username)->status(null)->one() ?? new User(['username' => $username, 'email' => $username.'@example.com']);
            $user->fullName = $name;
            $this->save($user, $name);
            $writers[$username] = $user;
        }

        foreach (self::BOOKS as [$slug, $title, $byline, $price, $blurb]) {
            $entry = $this->entry('books', $slug);
            $entry->title = $title;
            $entry->postDate = new DateTime('2026-09-01');
            $entry->setFieldValues([
                'byline' => $byline,
                'price' => $price,
                'blurb' => $blurb,
                'coverImage' => array_filter([$this->asset("covers/{$slug}.png")?->id]),
            ]);
            $this->save($entry, $title);
        }

        foreach (self::JOURNAL as [$slug, $title, $writer, $date, $excerpt]) {
            $entry = $this->entry('journal', $slug);
            $entry->title = $title;
            $entry->postDate = new DateTime($date);
            $entry->setAuthorIds([$writers[$writer]->id]);
            $entry->setFieldValues([
                'excerpt' => $excerpt,
                // The events post has no picture, to show the fallback.
                'featuredImage' => $slug === 'events' ? [] : array_filter([$this->asset("journal/{$slug}.png")?->id]),
                'body' => $excerpt ? $excerpt."\n\nMore soon from the shop floor." : 'Readings and signings at the shop, every first Thursday.',
            ]);
            $this->save($entry, $title);
        }

        $home = Entry::find()->section('home')->one();

        if ($home) {
            $home->title = 'Dog-ear Books';
            $this->save($home, 'Home');
        }

        $this->stdout("\nNow make the images: php craft social-images/generate\n", Console::FG_GREEN);

        return ExitCode::OK;
    }

    /**
     * Prints an entry's ID, for the screenshots script.
     */
    public function actionId(string $section, string $slug): int
    {
        $entry = Entry::find()->section($section)->slug($slug)->status(null)->one();

        if (! $entry) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout($entry->id."\n");

        return ExitCode::OK;
    }

    protected function entry(string $section, string $slug): Entry
    {
        $section = Craft::$app->getEntries()->getSectionByHandle($section);

        return Entry::find()->section($section->handle)->slug($slug)->status(null)->one()
            ?? new Entry(['sectionId' => $section->id, 'typeId' => $section->getEntryTypes()[0]->id, 'slug' => $slug]);
    }

    protected function asset(string $path): ?Asset
    {
        return Asset::find()->volume('images')->folderPath(dirname($path).'/')->filename(basename($path))->one();
    }

    protected function save($element, string $label): void
    {
        if (! Craft::$app->getElements()->saveElement($element)) {
            $this->stderr("  {$label}: ".implode(' ', $element->getFirstErrors())."\n", Console::FG_RED);

            return;
        }

        $this->stdout("  {$label}\n");
    }
}
