<?php

namespace pocketcloud\cloud\test;

spl_autoload_register(function ($class) {
    include basename($class) . ".php";
});

class TestA {

    #[MapKey(name: "hi")]
    private TestData $data;

    public function __construct(TestData $data) {
        $this->data = $data;
    }
}

class TestData {

    public string $name;
    public string|int $rizz;

    /**
     * @param string $name
     * @param string $rizz
     */
    public function __construct(string $name, string|int $rizz) {
        $this->name = $name;
        $this->rizz = $rizz;
    }
}

var_dump(MapperUtils::fromMap(MapperUtils::toMap(new TestA(new TestData("r3pt1s", "high"))), TestA::class));