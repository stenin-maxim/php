<?php

declare(strict_types=1);

namespace App\Http\Controllers\Recycler;

use App\Http\Requests\Recycler\UploadRecyclerProductionBuildingDocumentRequest;
use App\Traits\JsonResponseTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Recycler\SetRecyclerProductionBuildingRequest;
use App\Http\Requests\Recycler\SetRecyclerProductionSpotRequest;
use App\Http\Requests\Recycler\UploadRecyclerProductionSpotPhotoRequest;
use App\Models\Recycler\RecyclerProductionBuilding;
use App\Models\Recycler\RecyclerProductionSpot;
use App\Models\Recycler\RecyclerProductionSpotPhoto;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class RecyclerProductionSpotController extends Controller
{
    use JsonResponseTrait;

    public function getProductionSpots(): \Illuminate\Http\JsonResponse
    {
        try {
            $recycler_production_spots = RecyclerProductionSpot::where('company_id', Auth::user()->company_id)
                ->with(['recyclerProductionSpotPhotos' => function ($query) {
                    $query->where('is_main', '=', 1);
                }])
                ->get(); //TODO paginate()

            if (!$recycler_production_spots) {
                return $this->jsonFailureResponse('The company has no production spot yet');
            }

            $production_spots = $recycler_production_spots->toArray();
            foreach ($production_spots as &$production_spot) {
                if (isset($production_spot['recycler_production_spot_photos'][0]['id'])) {
                    $production_spot['main_photo'] = action([self::class, 'getResizedPhoto'], ['photo' => $production_spot['recycler_production_spot_photos'][0]['id']]);
                }
                unset($production_spot['recycler_production_spot_photos']);
            }

            return $this->jsonSuccessResponse(['production_spots' => $production_spots]);
        } catch (\Exception $e) {
            return $this->jsonFailureResponse($e->getMessage());
        }
    }

    public function getGeneralInfo(int $production_spot): \Illuminate\Http\JsonResponse
    {
        try {
            $recycler_production_spot = RecyclerProductionSpot::where([
                ['id', $production_spot],
                ['company_id', Auth::user()->company_id],
            ])
                ->with('recyclerProductionSpotPhotos')
                ->first();

            if (!$recycler_production_spot) {
                throw new \Exception('No such spot');
            }

            $production_spot_main_info = RecyclerProductionSpot::getSpotMainInfo($recycler_production_spot->toArray());
            $production_buildings = RecyclerProductionBuilding::getBuildingsBySpotId($recycler_production_spot['id']);

            return $this->jsonSuccessResponse([
                'production_spot_main_info' => $production_spot_main_info,
                'production_buildings' => $production_buildings,
            ]);
        } catch (\Exception $e) {
            return $this->jsonFailureResponse($e->getMessage());
        }
    }

    public function getResizedPhoto(int $id)
    {
        try {
            $recycler_production_spot_photo = RecyclerProductionSpotPhoto::find($id);

            if (Auth::user()->cannot('getPhoto', $recycler_production_spot_photo)) {
                throw new \Exception('No such file or user has no permissions');
            }

            if (!File::isFile(storage_path('app') . DIRECTORY_SEPARATOR . $recycler_production_spot_photo->resized_photo_path)) {
                throw new \Exception('No such file');
            }

            return response()->file(storage_path('app') . DIRECTORY_SEPARATOR . $recycler_production_spot_photo->resized_photo_path);
        } catch (\Exception $e) {
            return $this->jsonFailureResponse($e->getMessage());
        }
    }

    public function uploadProductionSpotPhotos(UploadRecyclerProductionSpotPhotoRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $validated_data = $request->validated();

            RecyclerProductionSpotPhoto::savePhotos($validated_data);
            RecyclerProductionSpotPhoto::saveMainPhoto($validated_data);

            return $this->jsonSuccessResponse();
        } catch (\Exception $e) {
            return $this->jsonFailureResponse($e->getMessage());
        }
    }

    public function createMainInfo(SetRecyclerProductionSpotRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $validated_data = $request->validated();

            $recycler_production_spot = new RecyclerProductionSpot();
            $recycler_production_spot->fill($validated_data)
                ->save();

            return $this->jsonSuccessResponse(
                ['recycler_production_spot_id' => $recycler_production_spot->id,],
                null,
                201);
        } catch (\Exception $e) {
            return $this->jsonFailureResponse($e->getMessage());
        }
    }

    public function updateMainInfo(int $id, SetRecyclerProductionSpotRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $validated_data = $request->validated();

            $recycler_production_spot = RecyclerProductionSpot::findOrFail($id); //TODO error message
            $recycler_production_spot->fill($validated_data)
                ->save();

            return $this->jsonSuccessResponse(['recycler_production_spot_id' => $recycler_production_spot->id]);
        } catch (\Exception $e) {
            return $this->jsonFailureResponse($e->getMessage());
        }
    }

    public function updateProductionBuilding(int $id, SetRecyclerProductionBuildingRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $validated_data = $request->validated();

            $recycler_production_building = RecyclerProductionBuilding::find($id);

            if (!$recycler_production_building) {
                throw new \Exception('No such building');
            }

            if ((int)$validated_data['is_owner'] === 1) {
                if ($recycler_production_building->rent_contract_file) {
                    File::delete(storage_path('app') . DIRECTORY_SEPARATOR . $recycler_production_building->rent_contract_file_path);
                }
                if ($recycler_production_building->rent_payments_file) {
                    File::delete(storage_path('app') . DIRECTORY_SEPARATOR . $recycler_production_building->rent_payments_file_path);
                }
                $recycler_production_building->rent_contract_file_path = null;
                $recycler_production_building->rent_payments_file_path = null;
                $recycler_production_building->rent_contract_number = null;
                $recycler_production_building->rent_term = null;
            } else {
                if ($recycler_production_building->title_deed_file_path) {
                    File::delete(storage_path('app') . DIRECTORY_SEPARATOR . $recycler_production_building->title_deed_file_path);
                }
                $recycler_production_building->title_deed_file_path = null;
                $recycler_production_building->title_deed_series = null;
                $recycler_production_building->title_deed_number = null;
            }

            $recycler_production_building->fill($validated_data)
                ->save();

            return $this->jsonSuccessResponse();
        } catch (\Exception $e) {
            return $this->jsonFailureResponse($e->getMessage());
        }
    }

    public function deletePhoto(int $id): \Illuminate\Http\JsonResponse
    {
        try {
            $recycler_production_spot_photo = RecyclerProductionSpotPhoto::find($id);

            if (!$recycler_production_spot_photo) {
                throw new \Exception('No such photo');
            }

            if (Auth::user()->cannot('deletePhoto', $recycler_production_spot_photo)) {
                throw new \Exception('User has no permission');
            }

            File::delete(storage_path('app') . DIRECTORY_SEPARATOR . $recycler_production_spot_photo->original_photo_path);
            File::delete(storage_path('app') . DIRECTORY_SEPARATOR . $recycler_production_spot_photo->resized_photo_path);

            RecyclerProductionSpotPhoto::destroy($id);

            return $this->jsonSuccessResponse();
        } catch (\Exception $e) {
            return $this->jsonFailureResponse($e->getMessage());
        }
    }

    public function createProductionBuilding(SetRecyclerProductionBuildingRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $recycler_production_building = new RecyclerProductionBuilding();
            $recycler_production_building->fill($request->validated())
                ->save();

            return $this->jsonSuccessResponse(
                ['production_building_id' => $recycler_production_building->id],
                null,
                201
            );
        } catch (\Exception $e) {
            return $this->jsonFailureResponse($e->getMessage());
        }
    }

    public function uploadSanitaryEpidemiologicalConclusionFile(UploadRecyclerProductionBuildingDocumentRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $validated_data = $request->validated();

            $model = RecyclerProductionBuilding::find($validated_data['production_building_id']);

            //TODO check permissions

            if (!$model) {
                throw new \Exception('No such building');
            }

            File::delete(storage_path('app') . DIRECTORY_SEPARATOR . $model->sanitary_epidemiological_conclusion_file_path);

            $model->sanitary_epidemiological_conclusion_file_path = $model->saveFile(
                $validated_data['file'],
                (int)$validated_data['production_building_id'],
                'sanitary_epidemiological_conclusion_file_path',
            );
            $model->save();

            return $this->jsonSuccessResponse();
        } catch (\Exception $e) {
            return $this->jsonFailureResponse($e->getMessage());
        }
    }

    public function uploadTitleDeedFile(UploadRecyclerProductionBuildingDocumentRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $validated_data = $request->validated();

            $model = RecyclerProductionBuilding::find($validated_data['production_building_id']);

            //TODO check permissions

            if (!$model) {
                throw new \Exception('No such building');
            }

            File::delete(storage_path('app') . DIRECTORY_SEPARATOR . $model->title_deed_file_path);

            $model->title_deed_file_path = $model->saveFile(
                $validated_data['file'],
                (int)$validated_data['production_building_id'],
                'title_deed_file_path',
            );
            $model->save();

            return $this->jsonSuccessResponse();
        } catch (\Exception $e) {
            return $this->jsonFailureResponse($e->getMessage());
        }
    }

    public function uploadRentPaymentsFile(UploadRecyclerProductionBuildingDocumentRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $validated_data = $request->validated();

            $model = RecyclerProductionBuilding::find($validated_data['production_building_id']);

            //TODO check permissions

            if (!$model) {
                throw new \Exception('No such building');
            }

            File::delete(storage_path('app') . DIRECTORY_SEPARATOR . $model->rent_payments_file_path);

            $model->rent_payments_file_path = $model->saveFile(
                $validated_data['file'],
                (int)$validated_data['production_building_id'],
                'rent_payments_file_path',
            );
            $model->save();

            return $this->jsonSuccessResponse();
        } catch (\Exception $e) {
            return $this->jsonFailureResponse($e->getMessage());
        }
    }

    public function uploadRentContractFile(UploadRecyclerProductionBuildingDocumentRequest $request)
    {
        try {
            $validated_data = $request->validated();

            $model = RecyclerProductionBuilding::find($validated_data['production_building_id']);

            //TODO check permissions

            if (!$model) {
                throw new \Exception('No such building');
            }

            File::delete(storage_path('app') . DIRECTORY_SEPARATOR . $model->rent_contract_file_path);

            $model->rent_contract_file_path = $model->saveFile(
                $validated_data['file'],
                (int)$validated_data['production_building_id'],
                'rent_contract_file_path',
            );
            $model->save();

            return $this->jsonSuccessResponse();
        } catch (\Exception $e) {
            return $this->jsonFailureResponse($e->getMessage());
        }
    }

    public function downloadTitleDeedFile(int $production_building): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
    {
        try {
            $recycler_production_building = RecyclerProductionBuilding::find($production_building);

            if (!$recycler_production_building || Auth::user()->cannot('downloadFile', $recycler_production_building)) {
                throw new \Exception('No such building or user has no permissions');
            }

            if (!File::isFile(storage_path('app') . DIRECTORY_SEPARATOR . $recycler_production_building->title_deed_file_path)) {
                throw new \Exception('No such file');
            }

            return response()->download(storage_path('app') . DIRECTORY_SEPARATOR . $recycler_production_building->title_deed_file_path);
        } catch (\Exception $e) {
            return $this->jsonFailureResponse($e->getMessage());
        }
    }

    public function downloadRentPaymentsFile(int $production_building): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
    {
        try {
            $recycler_production_building = RecyclerProductionBuilding::find($production_building);

            if (!$recycler_production_building || Auth::user()->cannot('downloadFile', $recycler_production_building)) {
                throw new \Exception('No such building or user has no permissions');
            }

            if (!File::isFile(storage_path('app') . DIRECTORY_SEPARATOR . $recycler_production_building->rent_payments_file_path)) {
                throw new \Exception('No such file');
            }

            return response()->download(storage_path('app') . DIRECTORY_SEPARATOR . $recycler_production_building->rent_payments_file_path);
        } catch (\Exception $e) {
            return $this->jsonFailureResponse($e->getMessage());
        }
    }

    public function downloadRentContractFile(int $production_building): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
    {
        try {
            $recycler_production_building = RecyclerProductionBuilding::find($production_building);

            if (!$recycler_production_building || Auth::user()->cannot('downloadFile', $recycler_production_building)) {
                throw new \Exception('No such building or user has no permissions');
            }

            if (!File::isFile(storage_path('app') . DIRECTORY_SEPARATOR . $recycler_production_building->rent_contract_file_path)) {
                throw new \Exception('No such file');
            }

            return response()->download(storage_path('app') . DIRECTORY_SEPARATOR . $recycler_production_building->rent_contract_file_path);
        } catch (\Exception $e) {
            return $this->jsonFailureResponse($e->getMessage());
        }
    }

    public function downloadSanitaryEpidemiologicalConclusionFile(int $production_building): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
    {
        try {
            $recycler_production_building = RecyclerProductionBuilding::find($production_building);

            if (!$recycler_production_building || Auth::user()->cannot('downloadFile', $recycler_production_building)) {
                throw new \Exception('No such building or user has no permissions');
            }

            if (!File::isFile(storage_path('app') . DIRECTORY_SEPARATOR . $recycler_production_building->sanitary_epidemiological_conclusion_file_path)) {
                throw new \Exception('No such file');
            }

            return response()->download(storage_path('app') . DIRECTORY_SEPARATOR . $recycler_production_building->sanitary_epidemiological_conclusion_file_path);
        } catch (\Exception $e) {
            return $this->jsonFailureResponse($e->getMessage());
        }
    }

}
