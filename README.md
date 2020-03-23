> ### Model

在config\app.php中的aliases中添加``'别名'=>'门面类'``的元素,并在bootstrap中添加对应的``provider``
```php
// config\app.php
'aliases' => [
    'PHPRedis' => Src\Core\Facades\Redis::class,
],
$app->initializeServices([
    ...
    Src\Provider\RedisServiceProvider::class,
]);
```
provider中设置的app键名对应门面类中getFacadeAccessor方法的返回值
```php
// Src\Provider\RedisServiceProvider
public function register()
{
     $this->app->set('php_redis', function () {
        return new RedisManager();
    });
}
// Src\Core\Facades\Redis
protected static function getFacadeAccessor()
{
    return 'php_redis';
}
```