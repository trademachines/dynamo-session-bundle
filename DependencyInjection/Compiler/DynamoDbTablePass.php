<?php
/**
 * Copyright (c) 2013 Gijs Kunze
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GWK\DynamoSessionBundle\DependencyInjection\Compiler;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Exception\ResourceNotFoundException;
use GWK\DynamoSessionBundle\Handler\SessionHandler;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class DynamoDbTablePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if(!$container->hasDefinition("dynamo_session_client") && !$container->hasAlias("dynamo_session_client")) {
            return;
        }

        if($container->getAlias('session.handler') != "dynamo_session_handler") {
            return;
        }

        /** @var $client DynamoDbClient */
        $client = $container->get("dynamo_session_client");

        $tableName = $container->getParameter("dynamo_session_table");

        try {
            $client->describeTable(array('TableName' => $tableName));
        } catch(DynamoDbException $e) {
            if ('ResourceNotFoundException' !== $e->getAwsErrorCode()) {
                throw $e;
            }

            /** @var $handler SessionHandler */
            $handler = $container->get("dynamo_session_handler");

            $read_capacity = $container->getParameter("dynamo_session_read_capacity");
            $write_capacity = $container->getParameter("dynamo_session_write_capacity");

            $handler->getHandler()->createSessionsTable($read_capacity, $write_capacity);
        }
    }
}
