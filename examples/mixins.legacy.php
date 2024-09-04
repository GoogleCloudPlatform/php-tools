<?php

namespace Google\Cloud\Samples\Dlp;

use Google\Cloud\SecretManager\V1\SecretManagerServiceClient;
use Google\Cloud\Iam\V1\Binding;
use Google\Type\Expr;

$secretManager = new SecretManagerServiceClient();
$resource = sprintf('projects/%s/instances/%s/databases/%s', $projectId, $instanceId, $databaseId);
$policy = $secretManager->getIamPolicy($resource);

// IAM conditions need at least version 3
if ($policy->getVersion() != 3) {
    $policy->setVersion(3);
}

$binding = new Binding([
    'role' => 'roles/spanner.fineGrainedAccessUser',
    'members' => [$iamMember],
    'condition' => new Expr([
        'title' => $title,
        'expression' => sprintf("resource.name.endsWith('/databaseRoles/%s')", $databaseRole)
    ])
]);
$policy->setBindings([$binding]);
$secretManager->setIamPolicy($resource, $policy);
