<?php

namespace Claromentis\ThankYou\Tags\Tests\Unit;

use Claromentis\Core\Audit\Audit;
use Claromentis\ThankYou\Tags\Api;
use Claromentis\ThankYou\Tags\Exceptions\TagNotFound;
use Claromentis\ThankYou\Tags\Tag;
use Claromentis\ThankYou\Tags\TagAcl;
use Claromentis\ThankYou\Tags\TagFactory;
use Claromentis\ThankYou\Tags\TagRepository;
use Date;
use DateTime;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use User;

class ApiTest extends TestCase
{
	private $api;

	private $audit_mock;

	private $tag_acl_mock;

	private $tag_factory_mock;

	private $tag_mock;

	private $tag_repository_mock;

	private $user_mock;

	public function SetUp()
	{
		$this->audit_mock          = $this->createMock(Audit::class);
		$this->tag_acl_mock        = $this->createMock(TagAcl::class);
		$this->tag_factory_mock    = $this->createMock(TagFactory::class);
		$this->tag_repository_mock = $this->createMock(TagRepository::class);
		$this->tag_mock            = $this->createMock(Tag::class);
		$this->user_mock           = $this->createMock(User::class);

		$this->api = new Api($this->audit_mock, $this->tag_repository_mock, $this->tag_factory_mock, $this->tag_acl_mock);
	}

	public function testGetTagSuccessful()
	{
		$this->tag_repository_mock->method('GetTags')->willReturn([1 => $this->tag_mock]);

		$this->assertSame($this->api->GetTag(1), $this->tag_mock);
	}

	public function testGetTagNotFound()
	{
		$this->tag_repository_mock->method('GetTags')->willReturn([]);
		$this->expectException(TagNotFound::class);

		$this->api->GetTag(1);
	}

	public function testGetTagsSuccessful()
	{
		$get_filtered_tags_return = [$this->tag_mock];
		$this->tag_repository_mock->method('GetFilteredTags')->willReturn($get_filtered_tags_return);

		$this->assertSame($this->api->GetTags(), $get_filtered_tags_return);
	}

	public function testGetTagsByIdSuccessful()
	{
		$get_tags_return = [629 => $this->tag_mock];
		$this->tag_repository_mock->method('GetTags')->willReturn($get_tags_return);

		$this->assertSame($this->api->GetTagsById([629]), $get_tags_return);
	}

	public function testGetTaggedTagsSuccessfulTagsFound()
	{
		$expected_result        = [1 => $this->tag_mock];
		$get_tagged_tags_return = [1 => $expected_result];
		$this->tag_repository_mock->method('GetTaggedsTags')->willReturn($get_tagged_tags_return);

		$this->assertSame($this->api->GetTaggedTags(1, 1337), $expected_result);
	}

	public function testGetTaggedTagsSuccessfulNoTags()
	{
		$expected_result        = [];
		$get_tagged_tags_return = [1 => $expected_result];
		$this->tag_repository_mock->method('GetTaggedsTags')->willReturn($get_tagged_tags_return);

		$this->assertSame($this->api->GetTaggedTags(1, 1337), $expected_result);
	}

	public function testGetTaggedsTagsSuccessful()
	{
		$get_tagged_tags_return = [1 => [1 => $this->tag_mock]];
		$expected_result        = $get_tagged_tags_return;
		$expected_result[2]     = [];
		$this->tag_repository_mock->method('GetTaggedsTags')->willReturn($expected_result);

		$this->assertSame($this->api->GetTaggedsTags([1, 2], 1337), $expected_result);
	}

	public function testGetTotalTagsSuccessful()
	{
		$expected_result = 3;
		$this->tag_repository_mock->method('GetTotalTags')->willReturn($expected_result);

		$this->assertSame($this->api->GetTotalTags(), $expected_result);
	}

	public function testGetTagsTaggedTotalsSuccessful()
	{
		$expected_result = [1 => 56, 2 => 0, 3 => 9];
		$this->tag_repository_mock->method('GetTagsTaggedTotals')->willReturn($expected_result);

		$this->assertSame($this->api->GetTagsTaggedTotals(), $expected_result);
	}

	public function testGetTagsTaggedTotalsFromIdsSuccessful()
	{
		$expected_result = [1 => 56, 2 => 0, 3 => 9];
		$this->tag_repository_mock->method('GetTagsTaggedTotalsFromIds')->willReturn($expected_result);

		$this->assertSame($this->api->GetTagsTaggedTotalsFromIds([1, 2, 3]), $expected_result);
	}

	public function testCreateSuccessful()
	{
		$name = 'A Name!';
		$this->tag_factory_mock->expects($this->once())->method('Create')->with($name)->willReturn($this->tag_mock);
		$this->tag_mock->expects($this->once())->method('SetCreatedBy')->with($this->user_mock);
		$this->tag_mock->expects($this->once())->method('SetCreatedDate')->with($this->callback(function ($parameter) {
			return $parameter instanceof Date;
		}));
		$this->tag_mock->expects($this->once())->method('SetModifiedBy')->with($this->user_mock);
		$this->tag_mock->expects($this->once())->method('SetModifiedDate')->with($this->callback(function ($parameter) {
			return $parameter instanceof Date;
		}));

		$this->assertSame($this->api->Create($this->user_mock, $name), $this->tag_mock);
	}

	public function testSaveSuccessfulNew()
	{
		$this->tag_repository_mock->expects($this->once())->method('Save')->with($this->tag_mock)->willReturnCallback(function ($tag) {
			/**
			 * @var MockObject $tag
			 */
			$tag->method('GetId')->willReturn(1);
		});
		$this->audit_mock->expects($this->once())->method('Store')->with(Audit::AUDIT_SUCCESS, $this->anything(), 'tag_create');

		$this->api->Save($this->tag_mock);
	}

	public function testSaveSuccessfulEdit()
	{
		$this->tag_mock->method('GetId')->willReturn(1);
		$this->tag_repository_mock->expects($this->once())->method('Save')->with($this->tag_mock);
		$this->audit_mock->expects($this->once())->method('Store')->with(Audit::AUDIT_SUCCESS, $this->anything(), 'tag_edit');

		$this->api->Save($this->tag_mock);
	}

	//WELCOME BACK! The next test for Api is Delete. You should also write Unit tests for the Tag Factory.
}
