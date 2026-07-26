<?php

namespace App\Repositories;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Response;
use App\Enums\UserPermissionsEnum;
use App\Exceptions\GeneralException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Contracts\UserRepositoryInterface;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct()
    {
        $this->model = new User();
    }

    /**
     * {@inheritdoc}
     */
    public function all()
    {
        return $this->builderAll()
            ->where('deleted_at', null)
            ->get();
    } //end all()

    public function getByPermission(array $permission)
    {
        return $this->builderAll()
            ->when($permission, function (Builder $query) use ($permission) {
                $query->whereHas('permissions', function ($query) use ($permission) {
                    return $query->whereIn('name', $permission);
                });
            })->where('deleted_at', null);
    } //end getByPermission()

    public function findByField($field, $value)
    {
        return $this->builderAll()
            ->where($field, $value)
            ->first();
    } //end findByField()

    public function whereIn($field, $fieldValues)
    {
        return $this->builderAll()
            ->whereIn($field, $fieldValues);
    } //end whereIn()

    public function find_user_by_email_or_phone_number($value)
    {
        return $this->builderAll()
            ->where('email', $value)
            ->orWhere('phone_number', $value)
            ->first();
    } //end find_user_by_email_or_phone_number()

    public function find_user_by_email_or_referral_code($value)
    {
        return $this->builderAll()
            ->where('email', $value)
            ->orWhere('referral_code', $value)
            ->first();
    } //end find_user_by_email_or_referral_code()

    public function firstOrCreate($find, $create)
    {
        return $this->builderAll()
            ->withTrashed()
            ->firstOrCreate($find, $create);
    }

    
    public function info()
    {
        return $this->builderAll()->selectRaw('count(*) as total')
            ->join('model_has_permissions', 'users.id', 'model_id')
            ->join('permissions', 'permissions.id', 'model_has_permissions.permission_id')
            ->selectRaw('count(case when permissions.name = \''.UserPermissionsEnum::USER().'\' then 1 end) as clients')
            ->selectRaw('count(case when permissions.name = \''.UserPermissionsEnum::FINANCE().'\' then 1 end) as finance')
            ->selectRaw('count(case when permissions.name = \''.UserPermissionsEnum::AUDITOR().'\' then 1 end) as auditor')
            ->selectRaw('count(case when permissions.name = \''.UserPermissionsEnum::MARKETTER().'\' then 1 end) as marketter')
            ->selectRaw('count(case when permissions.name = \''.UserPermissionsEnum::ADMIN().'\' then 1 end) as admins')
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): Model
    {
        // Create model instance.
        $model = $this->newModel($data);

        // Save model instance.
        $this->saveModel($model);

        return $model;
    } //end create()

    /**
     * {@inheritdoc}
     *
     * @throws GeneralException
     */
    public function update(int $id, array $data): Model
    {
        $model = $this->getSingle($id);

        if (! $model) {
            throw new GeneralException('User could not be found.', 404);
        }

        // Update model with information
        $model->fill($data);

        // Save model instance.
        $this->saveModel($model);

        return $model;
    } //end update()

    /**
     * {@inheritdoc}
     */
    public function updateWhereIn(string $field, array $fieldData, array $updateData)
    {
        return $this->builderAll()
            ->whereIn($field, $fieldData)
            ->update($updateData);
    } //end update()

    /**
     * {@inheritdoc}
     *
     * @throws GeneralException
     * @throws Exception
     */
    public function delete(int $id): bool
    {
        $model = $this->getSingle($id);
        if (! $model) {
            throw new GeneralException('User could not be found.', 404);
        }

        // Delete model instance.
        $this->deleteModel($model);

        return true;
    } //end delete()

    /**
     * {@inheritdoc}
     *
     * @throws GeneralException
     */
    public function firstOrCreateWithEmailOrPhoneNumber(array $emailAndPhoneNumberArray, array $data): Model
    {
        if (! isset($emailAndPhoneNumberArray['email']) || ! isset($emailAndPhoneNumberArray['phone_number'])) {
            throw new GeneralException('An error occurred.', Response::HTTP_BAD_REQUEST);
        }

        $user = $this->builderAll()
            ->where('email', $emailAndPhoneNumberArray['email'])
            ->orWhere('phone_number', $emailAndPhoneNumberArray['phone_number'])
            ->first();

        if ($user) {
            return $user;
        }

        return $this->create($data);
    } //end update()

    /**
     * {@inheritdoc}
     *
     * @throws GeneralException
     */
    public function updateOrCreateWithThrashed(array $uniqueFields, array $data): Model
    {
        return $this->model->withTrashed()->updateOrCreate($uniqueFields, $data);
    } //end update()
}
