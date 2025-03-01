<?php

/** @var modX $modx */
$modx =& $object->xpdo;

switch ($options[xPDOTransport::PACKAGE_ACTION]) {
    case xPDOTransport::ACTION_INSTALL:
    case xPDOTransport::ACTION_UPGRADE:
        $modx->log(xPDO::LOG_LEVEL_INFO, 'Checking access policies...');

        /* assign policy to admin group */
        $policy = $modx->getObject(\MODX\Revolution\modAccessPolicy::class, ['name' => 'AIKit Configuration Access']);
        $adminGroup = $modx->getObject(\MODX\Revolution\modUserGroup::class, ['name' => 'Administrator']);
        if ($policy && $adminGroup) {
            /**
             * Check if we need to apply any default accesses
             */
            $access = $modx->getObject(\MODX\Revolution\modAccessContext::class, [
                'target' => 'mgr',
                'principal_class' => \MODX\Revolution\modUserGroup::class,
                'principal' => $adminGroup->get('id'),
                'authority' => 9999,
                'policy' => $policy->get('id'),
            ]);
            if (!$access) {
                $modx->log(modX::LOG_LEVEL_WARN, 'Administrator user group does not yet have access to the AI Kit policy, so we\'ll add provide all usergroups with access to the manager access to the AI Kit policy.');
                /**
                 * Add the context access to all user groups that also have access to the manager
                 */
                $groups = $modx->getCollection(\MODX\Revolution\modUserGroup::class);
                foreach ($groups as $group) {
                    $hasMgrAccess = $modx->getCount(\MODX\Revolution\modAccessContext::class, array(
                        'target' => 'mgr',
                        'principal_class' => \MODX\Revolution\modUserGroup::class,
                        'principal' => $group->get('id'),
                    ));

                    if ($hasMgrAccess > 0) {
                        $access = $modx->newObject(\MODX\Revolution\modAccessContext::class);
                        $access->fromArray(array(
                            'target' => 'mgr',
                            'principal_class' => \MODX\Revolution\modUserGroup::class,
                            'principal' => $group->get('id'),
                            'authority' => 9999,
                            'policy' => $policy->get('id'),
                        ));
                        if ($access->save()) {
                            $modx->log(modX::LOG_LEVEL_INFO, '- Added a Context Policy for user group ' . $group->get('name') . ' for full AI Kit access.');
                        }
                    } else {
                        $modx->log(modX::LOG_LEVEL_INFO, '- Skipping user group ' . $group->get('name') . '; they don\'t seem to have manager access.');
                    }
                }
            } else {
                $modx->log(modX::LOG_LEVEL_INFO, 'As the Administrator user group already has access to the AI Kit Policy, we will not set up any permissions right now.');
            }
        }

        // flush permissions
        $ctxQuery = $modx->newQuery(\MODX\Revolution\modContext::class);
        $ctxQuery->select($modx->getSelectColumns(\MODX\Revolution\modContext::class, '', '', array('key')));
        if ($ctxQuery->prepare() && $ctxQuery->stmt->execute()) {
            $contexts = $ctxQuery->stmt->fetchAll(PDO::FETCH_COLUMN);
            if ($contexts) {
                $serialized = serialize($contexts);
                $modx->exec("UPDATE {$modx->getTableName('modUser')} SET {$modx->escape('session_stale')} = {$modx->quote($serialized)}");
            }
        }
        break;
}
return true;
