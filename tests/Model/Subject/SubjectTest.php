<?php declare(strict_types=1);

namespace Fazland\FattureInCloud\Tests\Model\Subject;

use Fazland\FattureInCloud\Model\Subject\Subject;
use PHPUnit\Framework\TestCase;

class SubjectTest extends TestCase
{
    /**
     * @var Subject
     */
    private $subject;

    protected function setUp()
    {
        $this->subject = $this->createSubject();
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testSetShouldNotEmitNotices(): void
    {
        set_error_handler(function (int $errno, string $errstr) {
            throw new \Exception($errstr);
        }, E_ALL | E_STRICT);

        try {
            $this->subject->phone = '+393332223456';
            $this->subject->fax = '+393332223456';
        } finally {
            restore_error_handler();
        }
    }

    protected function createSubject(): Subject
    {
        return new ConcreteSubject();
    }
}

class ConcreteSubject extends Subject
{
}
