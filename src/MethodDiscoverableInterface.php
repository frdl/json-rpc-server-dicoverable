<?php

declare(strict_types=1);

namespace frdlweb\Api\Rpc;

use stdClass;

/**
 * Contract for JSON-RPC 2.0 procedures.
 */
interface MethodDiscoverableInterface extends \UMA\JsonRpc\Procedure
{
    public function discover(MethodDiscoverableInterface $DiscoverMethod) : void;
    public function getResultSpec(): ?\stdClass;
    public function getSummary(): ?string;
	public function getDescription(): ?string;
	public function getLinks(): ?array;
	public function getExamples(): ?array;
	public function getParametersSpec(): ?array;
}
