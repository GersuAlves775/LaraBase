# laravelBase
Base for laravel fast development.

# Como instalar
```composer require gerson/laravel-base ```

Vamos abrir o arquivo
```config/app.php```

e procurar por 
```'providers' => [ ...```

ao final do array, basta adicionar
```gersonalves\laravelBase\BaseLaravelServiceProvider::class```

Em seu projeto, dentro de App crie dois diretórios.
<strong>Services</strong> e <strong>Repositories</strong>

No arquivo "Controller.php" dentro de app/Http/Controllers
Substituia

```    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;```

por

```use AuthorizesRequests, DispatchesJobs, ValidatesRequests, ControllerTrait;```

E adicione no cabeçalho do arquivo.

```
use gersonalves\laravelBase\Traits\ControllerTrait;
```

# Como utilizar
A utilização é bem simples, você poderá gerar suas resources com um simples comando.
O unico requisito é que já tenha gerado sua <strong>MODEL</strong>.

Basta executar o comando
```php artisan larabase:resource```
e responder as perguntas.
Sugestão: Utilize o nome da Model na primeira pergunta (Que irá perguntar qual o nome do service).
Caso diga sim para todos os itens, ao final você terá
1 Repository
1 - Service
1 - Controller. (Já com o CRUD pronto para ser executado).

# Como funciona

  O metodo get() irá buscar todos os itens da sua model, é possível aplicar as relações, basta criar uma função em sua model com o nome de 
  scopeWithRelations, segue o exemplo:
  
  ```php
    public function scopeWithRelations(Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->with(['address', 'people']);
    } 
  ```
  
  Assim ela será aplicado para o metodo INDEX e SHOW.
  
  Já para persistencia (Store e Update) ele irá persistir caso os indices que vem da requisição sejam iguais ao nome das colunas no banco de dados. 
Obs: o seu Fillable precisa estar com essas colunas, é de lá que ele irá extrair.
Além disso, você pode sobrescrever o metodo store e update em seu Service. assim aplicando suas regras especificas, veja outro exemplo:

```php 
  public function store(Request $request)
    {
        $eventAddress = new EventAddressRepository();
        $address = $eventAddress->store(new Request($request->get('event_address')));
        $request = $this->mergeRequest($request, ['id_event_address' => $address['id_event_address']]);
        $events = $this->repository->store($request);
        return array_merge($events, ['event_address' => $address]);
    }
```

Dessa forma, na mesma requisição eu consigo persistir um evento e também o seu endereço, escrevendo poucas linhas de código. 
