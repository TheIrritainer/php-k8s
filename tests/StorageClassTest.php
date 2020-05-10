<?php

namespace RenokiCo\PhpK8s\Test;

use RenokiCo\PhpK8s\Exceptions\KubernetesAPIException;
use RenokiCo\PhpK8s\K8s;
use RenokiCo\PhpK8s\Kinds\K8sStorageClass;
use RenokiCo\PhpK8s\ResourcesList;

class StorageClassTest extends TestCase
{
    public function test_storage_class_build()
    {
        $sc = K8s::storageClass()
            ->setName('io1')
            ->setProvisioner('csi.aws.amazon.com')
            ->setParameters(['type' => 'io1', 'iopsPerGB' => 10])
            ->setMountOptions(['debug']);

        $this->assertEquals('storage.k8s.io/v1', $sc->getApiVersion());
        $this->assertEquals('io1', $sc->getName());
        $this->assertEquals('csi.aws.amazon.com', $sc->getProvisioner());
        $this->assertEquals(['type' => 'io1', 'iopsPerGB' => 10], $sc->getParameters());
        $this->assertEquals(['debug'], $sc->getMountOptions());
    }

    public function test_storage_class_create()
    {
        $sc = K8s::storageClass()
            ->onCluster($this->cluster)
            ->setName('io1')
            ->setProvisioner('csi.aws.amazon.com')
            ->setParameters(['type' => 'io1', 'iopsPerGB' => '10'])
            ->setMountOptions(['debug']);

        $this->assertFalse($sc->isSynced());

        $sc = $sc->create();

        $this->assertTrue($sc->isSynced());

        $this->assertInstanceOf(K8sStorageClass::class, $sc);

        $this->assertEquals('storage.k8s.io/v1', $sc->getApiVersion());
        $this->assertEquals('io1', $sc->getName());
        $this->assertEquals('csi.aws.amazon.com', $sc->getProvisioner());
        $this->assertEquals(['type' => 'io1', 'iopsPerGB' => 10], $sc->getParameters());
        $this->assertEquals(['debug'], $sc->getMountOptions());
    }

    public function test_storage_class_all()
    {
        $storageClasses = K8s::storageClass()
            ->onCluster($this->cluster)
            ->all();

        $this->assertInstanceOf(ResourcesList::class, $storageClasses);

        foreach ($storageClasses as $sc) {
            $this->assertInstanceOf(K8sStorageClass::class, $sc);

            $this->assertNotNull($sc->getName());
        }
    }

    public function test_storage_class_get()
    {
        $sc = K8s::storageClass()
            ->onCluster($this->cluster)
            ->whereName('io1')
            ->get();

        $this->assertInstanceOf(K8sStorageClass::class, $sc);

        $this->assertTrue($sc->isSynced());

        $this->assertEquals('storage.k8s.io/v1', $sc->getApiVersion());
        $this->assertEquals('io1', $sc->getName());
        $this->assertEquals('csi.aws.amazon.com', $sc->getProvisioner());
        $this->assertEquals(['type' => 'io1', 'iopsPerGB' => 10], $sc->getParameters());
        $this->assertEquals(['debug'], $sc->getMountOptions());
    }

    public function test_storage_class_update()
    {
        $sc = K8s::storageClass()
            ->onCluster($this->cluster)
            ->whereName('io1')
            ->get();

        $this->assertTrue($sc->isSynced());

        $sc->setAttribute('mountOptions', ['debug']);

        $this->assertTrue($sc->update());

        $this->assertTrue($sc->isSynced());

        $this->assertEquals('storage.k8s.io/v1', $sc->getApiVersion());
        $this->assertEquals('io1', $sc->getName());
        $this->assertEquals(['debug'], $sc->getAttribute('mountOptions'));
        $this->assertEquals(['type' => 'io1', 'iopsPerGB' => '10'], $sc->getParameters());
        $this->assertEquals(['debug'], $sc->getMountOptions());
    }

    public function test_storage_class_watch_all()
    {
        $watch = K8s::storageClass()
            ->onCluster($this->cluster)
            ->watchAll(function ($type, $sc) {
                if ($sc->getName() === 'io1') {
                    return true;
                }
            }, ['timeoutSeconds' => 10]);

        $this->assertTrue($watch);
    }

    public function test_storage_class_watch_resource()
    {
        $watch = K8s::storageClass()
            ->onCluster($this->cluster)
            ->whereName('io1')
            ->watch(function ($type, $sc) {
                return $sc->getName() === 'io1';
            }, ['timeoutSeconds' => 10]);

        $this->assertTrue($watch);
    }

    public function test_storage_class_delete()
    {
        $sc = K8s::storageClass()
            ->onCluster($this->cluster)
            ->whereName('io1')
            ->get();

        $this->assertTrue($sc->delete());

        $this->expectException(KubernetesAPIException::class);

        $sc = K8s::storageClass()
            ->onCluster($this->cluster)
            ->whereName('io1')
            ->get();
    }
}
