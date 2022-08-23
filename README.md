# Requisitos
```PHP ^8.1```

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
Além disso, você pode sobrescrever o metodo store e update em seu Service. assim aplicando suas regras especificas.

# Alguns recursos

Em sua controller, voce pode configurar a validacao da seguinte forma:

# #1 - Controllers
```php 
  protected ?array $validators = [
        'email' => 'required|unique:users,email',
        'password' => 'required|min:8|string',
        'cpf' => 'required|cpf|formato_cpf',
    ];
```
Assim, qualquer requisicao de store ou update ira aplicar essas regras. Mas ai, temos um problema. 
Por informar que o e-mail deve ser unico, o update ira quebrar falando que o e-mail ja existe, ja que a validacao eh aplicada antes,
para resolver isso, tempos duas opcoes, a primeira eh sobrescrever a regra do e-mail para quando for um update, da seguinte maneira:


```php 
  protected ?array $replaceOnUpdate = [
        'email' => 'required'
    ];
```
Dessa forma, ele ira fazer um merge entre os dois arrays, quando update e o e-mail continuara requerido, porem, sem a validacao de unico. 
Alem disso, ele ira manter as demais regras, para cpf e password, caso eu queira remove-los, posso fazer da seguinte forma:

```php 
  protected ?array $excludeOnUpdate = ['cpf', 'password'];
```

Dessa forma, usando os 3, o resultado sera, aplicara apenas o $validators ao criar um novo registro e ao atualizar, apenas ira obrigar a preencher o E-mail.


# #2 - Services
Em nossa versao anterior, precisavamos fazer assim para salver algo em multi nivel:
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

Com a nova versao, nao precisamos mais disso, veja o exemplo:
```php 
 protected ?array $parentStore = [
        EventAddressService::class => PersistEnum::BEFORE_PERSIST
    ];
```
Eu preciso apenas informar o Repository que vira como "filho" do objeto e em qual momento deve ser persistido, antes ou depois do principal.


# #3 - Repository
Caso eu precise armazenar uma imagem, considerando que irei recebe-la em base64 via requisicao JSON, no seguinte exemplo:
```json 
  {
    "logo": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVQYV2NgYAAAAAMAAWgmWQ0AAAAASUVORK5CYII="
  }

```

Eu posso configurar em meu repository o seguinte:
```php 
  protected ?array $storeFile = [
        'logo' => [
            'type' => fileEnum::BASE64_IMAGE,
            'path' => 'public/'
        ]
    ];
```
Dessa forma, ele ira converter o base64 em um arquivo, salva-lo no storage no path pre-definido e me devolver a URL de acesso ao arquivo e adicionar isso para salva-la no banco caso exista uma coluna com o nome de "logo" nesse caso.
algo tipo:
```json 
  {
    "logo": "localhost/storage/asdf-asdf-asdf-asdf.png"
  }

```
