<?php

namespace App\Http\Controllers\Api;

use Hash;
use App\User;
use Validator;
use Illuminate\Http\Request;
use App\Helpers\CommonHelper;
use App\Helpers\ImageHelper;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Response;
use DB;
use App\Mail\NotifyMail;
use Mail;
use App\Models\Ad;
use App;

class HomeController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
     public function __construct(Request $request){
         if($request->lang){
             App::setlocale($request->lang);
         }
     }

     public function getCategories(){
         $categories = Category::whereNull('parent_id')->where('is_active','1')->whereNull('deleted_at')->get();
         $data = array();
         if($categories->toArray()){
            foreach ($categories as $key => $category) {
              $temp = [];
              $temp['category_id'] = $category->id;
              $temp['title']       = $category->title_name;
              $temp['image']       = $category->image;
              array_push($data,$temp);
            }
         return ['status' => true,'message'=> __('Record found'),'data'=>$data];
         }
        return ['status' => false ,'message'=> __('Record not found')];
     }

     public function home(Request $request){
          
         $categoriesData = Category::whereNull('parent_id')->where('is_active','1')->whereNull('deleted_at')->get();
         $categories  = array();
         if($categoriesData->toArray()){
            foreach ($categoriesData as $key => $category) {
              $temp = [];
              $temp['category_id'] = $category->id;
              $temp['title']       = $category->title_name;
              $temp['image']       = $category->image;
              array_push($categories,$temp);
             }
         }
        
         $adsData = Ad::select('ads.*')->join('users','ads.user_id','=','users.id')
                    ->where('ads.is_active','1')
                    ->where('ads.is_publish','1')
                    ->where('ads.is_approved','1')
                    ->where('users.is_active','1')
                    ->whereNull('ads.deleted_at')
                    ->whereNull('users.deleted_at')
                    ->get();
         $ads = array();
         if($adsData->toArray()){
            foreach ($adsData as $key => $ad) {
              $temp = [];
              $temp['ad_id']  = $ad->id;
              $temp['title']  = $ad->title;
              $temp['is_featured']  = $ad->is_featured;
              $temp['image']  = $ad->image;
              $temp['price']  = $ad->price;
              $temp['category']  = $ad->category->title_name;
              $temp['sub_category']  = $ad->subcategory->title_name;
              $isFavourite = 0;
              if($request->user_id){
                  $isFavourite = DB::table('favouriate_ads')->where('user_id',$request->user_id)->where('ad_id',$ad->id)->count();
                  $isFavourite = $isFavourite > 0 ? '1' : '0';
              }
              $temp['is_favouriate'] = $isFavourite;
              $temp['is_sell']       = '1';
              $temp['location'] = $ad->area->title_name .' ('.$ad->city->title_name .')';
              array_push($ads,$temp);
            }
         }

         $data['categories'] = $categories;
         $data['ads']        = $ads;
        
         return ['status'=>true,'message'=> __('Record not') , 'data' => $data ];
     }
             
     public function getAds(Request $request){
        
         $adsData = Ad::select('ads.*')
                    ->join('users','ads.user_id','=','users.id')
                    ->join('categories','ads.category_id','=','ads.category_id')
                    ->join('cities','ads.city_id','=','cities.id')
                    ->join('city_areas','ads.city_area_id','=','city_areas.id')
                    ->where('ads.is_active','1')
                    ->where('ads.is_publish','1')
                    ->where('ads.is_approved','1')
                    ->where('users.is_active','1')
                    ->whereNull('ads.deleted_at')
                    ->whereNull('users.deleted_at')
                    ->where(function($query) use ($request){

                       
                       if(!empty($request->user_id) && !is_null($request->user_id)){
                          $query->where('ads.user_id',$request->user_id);
                       }
                       
                       if(!empty($request->user_id) && !is_null($request->category_id)){
                          $query->where('ads.category_id',$request->category_id);
                       }
                       
                       if(!empty($request->user_id) && !is_null($request->sub_category_id)){
                          $query->where('ads.sub_category_id',$request->sub_category_id);
                       }
                       
                       if(!empty($request->user_id) && !is_null($request->city_id)){
                          $query->where('ads.city_id',$request->city_id);
                       }
                       
                       if(!empty($request->user_id) && !is_null($request->area_id)){
                          $query->where('ads.city_area_id',$request->area_id);
                       }
                       
                       if(!empty($request->user_id) && !is_null($request->is_featured)){
                          $query->where('ads.is_featured',$request->is_featured);
                       }

                       if(!empty($request->user_id) && !is_null($request->search)){
                          $query->whereRaw('LOWER(ads.title) like ?', '%'.strtolower(trim($request->search)).'%');
                          $query->orWhereRaw('LOWER(categories.title) like ?', '%'.strtolower(trim($request->search)).'%');
                          $query->orWhereRaw('LOWER(cities.title) like ?', '%'.strtolower(trim($request->search)).'%');
                          $query->orWhereRaw('LOWER(city_areas.title) like ?', '%'.strtolower(trim($request->search)).'%');
                       }
                      
                       if(!empty($request->user_id) && !is_null($request->min)){
                          $query->where('ads.price','>=',$request->min);
                       }

                       if(!empty($request->user_id) && !is_null($request->max)){
                         $query->where('ads.price','<=',$request->max);
                       }

                    })
                    ->get();

         $ads = array();
         if($adsData->toArray()){
            foreach ($adsData as $key => $ad) {
              $temp = [];
              $temp['ad_id']  = $ad->id;
              $temp['title']  = $ad->title;
              $temp['is_featured']  = $ad->is_featured;
              $temp['image']  = $ad->image;
              $temp['price']  = $ad->price;
              $temp['category']  = $ad->category->title_name;
              $temp['sub_category']  = $ad->subcategory->title_name;
              $isFavourite = 0;
              if($request->user_id){
                  $isFavourite = DB::table('favouriate_ads')->where('user_id',$request->user_id)->where('ad_id',$ad->id)->count();
                  $isFavourite = $isFavourite > 0 ? '1' : '0';
              }
              $temp['is_favouriate'] = $isFavourite;
              $temp['is_sell']       = '1';
              $temp['location'] = $ad->area->title_name .' ('.$ad->city->title_name .')';
              array_push($ads,$temp);
            }
           return ['status'=>true,'message'=> __('Record not') , 'data' => $ads ];
         }else{
           return ['status'=>false,'message'=> __('Record not not') ];
         }


     }

     public function getAd(Request $request){
         $inputs         = $request->all();

          $rules = [
                     'ad_id'  => 'required',
                    ];

           $validator = Validator::make($request->all(), $rules);

         if ($validator->fails()) {
             $errors =  $validator->errors()->all();
             return response(['status' => false , 'message' => $errors[0]] , 200);              
         }

         $adData = Ad::select('ads.*')
                    ->join('users','ads.user_id','=','users.id')
                    ->join('categories','ads.category_id','=','ads.category_id')
                    ->join('cities','ads.city_id','=','cities.id')
                    ->join('city_areas','ads.city_area_id','=','city_areas.id')
                    ->where('ads.is_active','1')
                    ->where('ads.is_publish','1')
                    ->where('ads.is_approved','1')
                    ->where('users.is_active','1')
                    ->whereNull('ads.deleted_at')
                    ->whereNull('users.deleted_at')
                    ->where('ads.id',$inputs['ad_id'])
                    ->first();

          $adData = Ad::select('ads.*')
                    ->join('users','ads.user_id','=','users.id')
                    ->join('categories','ads.category_id','=','ads.category_id')
                    ->join('cities','ads.city_id','=','cities.id')
                    ->join('city_areas','ads.city_area_id','=','city_areas.id')
                    ->whereNull('ads.deleted_at')
                    ->whereNull('users.deleted_at')
                    ->where('ads.id',$inputs['ad_id'])
                    ->first();

        if($adData){
              $ad = [];
              $ad['ad_id']        = $adData->id;
              $ad['title']        = $adData->title;
              $ad['description']  = $adData->description;
              $ad['condition_id'] = $adData->condition;
              $ad['condition']    = $adData->condition ? __('New') : __('Used');
              $ad['is_featured']  = $adData->is_featured;
              $ad['images']       = $adData->images;
              $ad['price']        = $adData->price;
              $ad['category_id']      = $adData->category_id;
              $ad['sub_category_id']  = $adData->sub_category_id;
              $ad['category']     = $adData->category->title_name;
              $ad['sub_category'] = $adData->subcategory->title_name;
              $ad['brand'] = $adData->brand;
              $isFavourite        = 0;
              if($request->user_id){
                  $isFavourite = DB::table('favouriate_ads')->where('user_id',$request->user_id)->where('ad_id',$adData->id)->count();
                  $isFavourite = $isFavourite > 0 ? '1' : '0';
              }
              $ad['is_favouriate'] = $isFavourite;
              $ad['is_sell']          = '1';
              $ad['city_id']          = $adData->city_id;
              $ad['city_are_id']      = $adData->city_area_id;
              $ad['location']         = $adData->area->title_name .' ('.$adData->city->title_name .')';
              $ad['ad_date']          = date('Y-m-d H:i:s',strtotime($adData->created_at));
              $ad['seller_id']        = $adData->user_id;
              $ad['seller_name']      = $adData->user->name;
              $ad['seller_profile']   = $adData->user->profile_image;
              $ad['seller_registration_date']   = date('Y-m-d',strtotime($adData->user->created_at));
              $ad['seller_email']     = $adData->user->email;
              $ad['seller_phone']     = $adData->user->phone;

           return ['status'=>true,'message'=> __('Record found') , 'data' => $ad ];
         }else{
           return ['status'=>false,'message'=> __('Record not not') ];
         }
   
      }

     public function doFavouriteAd(Request $request){
        
          $inputs         = $request->all();

          $rules = [
                     'user_id'      => 'required',
                     'ad_id'        => 'required',
                    ];

           $validator = Validator::make($request->all(), $rules);

         if ($validator->fails()) {
             $errors =  $validator->errors()->all();
             return response(['status' => false , 'message' => $errors[0]] , 200);              
         }     
        
         $isFavourite = DB::table('favouriate_ads')->where(['user_id'=>$inputs['user_id'],'ad_id'=>$inputs['ad_id']])->first();

         if($isFavourite){
          if(DB::table('favouriate_ads')->where(['user_id'=>$inputs['user_id'],'ad_id'=>$inputs['ad_id']])->delete()){
             return ['status' => true,'message'=> __('Success')];
           }else{
             return ['status' => false  ,'message'=> __('Failed')];
           }
         }else{
           if(DB::table('favouriate_ads')->insertGetId(['user_id'=>$inputs['user_id'],'ad_id'=>$inputs['ad_id']])){
             return ['status' => true,'message'=> __('Success')];
           }else{
             return ['status'=>true,'message'=>__('Failed')];
           }
         }
     }

     public function getFavouriteAds(Request $request){
         
          $inputs         = $request->all();

          $rules = [
                     'user_id'      => 'required',
                    ];

           $validator = Validator::make($request->all(), $rules);

         if ($validator->fails()) {
             $errors =  $validator->errors()->all();
             return response(['status' => false , 'message' => $errors[0]] , 200);              
         }

        $adsData = Ad::select('ads.*')
                    ->join('favouriate_ads','ads.id','=','favouriate_ads.ad_id')
                    ->join('users','ads.user_id','=','users.id')
                    ->where('ads.is_active','1')
                    ->where('ads.is_publish','1')
                    ->where('ads.is_approved','1')
                    ->where('users.is_active','1')
                    ->whereNull('ads.deleted_at')
                    ->whereNull('users.deleted_at')
                    ->where('favouriate_ads.user_id',$inputs['user_id'])
                    ->get();

         $ads = array();
         if($adsData->toArray()){
            foreach ($adsData as $key => $ad) {
              $temp = [];
              $temp['ad_id']  = $ad->id;
              $temp['title']  = $ad->title;
              $temp['is_featured']  = $ad->is_featured;
              $temp['image']  = $ad->image;
              $temp['price']  = $ad->price;
              $temp['category']  = $ad->category->title_name;
              $temp['sub_category']  = $ad->subcategory->title_name;
              $isFavourite = 1;
              $temp['is_favouriate'] = $isFavourite;
              $temp['is_sell']       = '1';
              $temp['location'] = $ad->area->title_name .' ('.$ad->city->title_name .')';
              array_push($ads,$temp);
            }
         }

         return ['status'=>true,'message'=> __('Record not') , 'data' => $ads ];
     }

     public function randomPassword() {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < 6; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass); //turn the array into a string
    }

    public function getSubCategories(Request $request){
          
          $inputs         = $request->all();

          $rules = [
                     'category_id'      => 'required',
                    ];

           $validator = Validator::make($request->all(), $rules);

         if ($validator->fails()) {
             $errors =  $validator->errors()->all();
             return response(['status' => false , 'message' => $errors[0]] , 200);              
         }

         $categories = Category::where('parent_id',$inputs['category_id'])->where('is_active','1')->whereNull('deleted_at')->orderBy('categories.title','asc')->get();
         $data = array();
         if($categories->toArray()){
            foreach ($categories as $key => $category) {
              $temp = [];
              $temp['sub_category_id'] = $category->id;
              $temp['title']       = $category->title_name;
              array_push($data,$temp);
            }
         return ['status' => true,'message'=> __('Record found'),'data'=>$data];
         }
        return ['status' => false ,'message'=> __('Record not found')];
    }

    public function adAdd(Request $request){
       $input = $request->all();
       $rules = [
            'user_id'           => 'required',
           'category_id'        => 'required',
           'sub_category_id'    => 'required',
           'city_id'            => 'required',
           'city_area_id'      => 'required',
           'title'              => 'required',
           'description'        => 'required',
           'price'              => 'required',
           'condition'          => 'required',
        ];
      
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
           $errors =  $validator->errors()->all();
           return response(['status' => false , 'message' => $errors[0]]);              
        }

        $adData = [
          'category_id'     => $input['category_id'],
          'sub_category_id' => $input['sub_category_id'],
          'title'           => $input['title'],
          'description'     => $input['description'],
          'price'           => $input['price'],
          'city_id'         => $input['city_id'],
          'city_area_id'    => $input['city_area_id'],
          'user_id'         => $input['user_id'],
          'condition'       => $input['condition'],
          'brand'           => $input['brand'] ?? NULL
        ];
        DB::beginTransaction();
        try {
         $adId =  DB::table('ads')->insertGetId($adData);
         $imageData = array();
         if($request->hasFile('image')){
            foreach ($request->image as $key => $image) {
                $imageName = str_random('10').'.'.time().'.'.$image->getClientOriginalExtension();
                $image->move(public_path('images/ad/'), $imageName);
                array_push($imageData, ['ad_id' => $adId,'name' => $imageName]);
            }
         }       
          if($imageData)
              DB::table('ad_images')->insert($imageData);
          DB::commit();
          return ['status'=> true ,'message'=>__('Successfully added ad')];
        } catch ( \Exception $e) {
          DB::rollback();
            return ['status'=> false ,'message'=> __('Failed to add ad') ];
        }
    }

    public function getMyAds(Request $request){

          $inputs = $request->all();

          $rules = [
                     'user_id' => 'required',
                    ];

           $validator = Validator::make($request->all(), $rules);

         if ($validator->fails()) {
             $errors =  $validator->errors()->all();
             return response(['status' => false , 'message' => $errors[0]] , 200);              
         }

           $adsData = Ad::select('ads.*')
                    ->join('categories','ads.category_id','=','ads.category_id')
                    ->join('cities','ads.city_id','=','cities.id')
                    ->join('city_areas','ads.city_area_id','=','city_areas.id')
                    ->whereNull('ads.deleted_at')
                    ->where('ads.user_id',$inputs['user_id'])
                    ->get();

         $ads = array();
         if($adsData->toArray()){
            foreach ($adsData as $key => $ad) {
              $temp = [];
              $temp['ad_id']  = $ad->id;
              $temp['title']  = $ad->title;
              $temp['is_featured']  = $ad->is_featured;
              $temp['image']  = $ad->image;
              $temp['price']  = $ad->price;
              $temp['category']  = $ad->category->title_name;
              $temp['sub_category']  = $ad->subcategory->title_name;
              $isFavourite = 0;
              if($request->user_id){
                  $isFavourite = DB::table('favouriate_ads')->where('user_id',$request->user_id)->where('ad_id',$ad->id)->count();
                  $isFavourite = $isFavourite > 0 ? '1' : '0';
              }
              $temp['is_favouriate'] = $isFavourite;
              $temp['is_sell']       = '1';
              $temp['location'] = $ad->area->title_name .' ('.$ad->city->title_name .')';
              array_push($ads,$temp);
            }
           return ['status'=>true,'message'=> __('Record not') , 'data' => $ads ];
         }else{
           return ['status'=>false,'message'=> __('Record not not') ];
         }
    }

    public function getAdDetails(Request $request){
          
              $inputs         = $request->all();

              $rules = [
                         'ad_id'  => 'required',
                        ];

               $validator = Validator::make($request->all(), $rules);

             if ($validator->fails()) {
                 $errors =  $validator->errors()->all();
                 return response(['status' => false , 'message' => $errors[0]] , 200);              
             }

             $adData = Ad::select('ads.*')
                        ->join('users','ads.user_id','=','users.id')
                        ->join('categories','ads.category_id','=','ads.category_id')
                        ->join('cities','ads.city_id','=','cities.id')
                        ->join('city_areas','ads.city_area_id','=','city_areas.id')
                        ->whereNull('ads.deleted_at')
                        ->whereNull('users.deleted_at')
                        ->where('ads.id',$inputs['ad_id'])
                        ->first();

            if($adData){
                  $ad = [];
                  $ad['ad_id']        = $adData->id;
                  $ad['title']        = $adData->title;
                  $ad['description']  = $adData->description;
                  $ad['condition_id'] = $adData->condition;
                  $ad['condition']    = $adData->condition ? __('New') : __('Used');
                  $ad['is_featured']  = $adData->is_featured;
                  $ad['images']       = $adData->images;
                  $ad['price']        = $adData->price;
                  $ad['category_id']      = $adData->category_id;
                  $ad['sub_category_id']  = $adData->sub_category_id;
                  $ad['category']     = $adData->category->title_name;
                  $ad['sub_category'] = $adData->subcategory->title_name;
                  $ad['brand'] = $adData->brand;
                  $isFavourite        = 0;
                  if($request->user_id){
                      $isFavourite = DB::table('favouriate_ads')->where('user_id',$request->user_id)->where('ad_id',$adData->id)->count();
                      $isFavourite = $isFavourite > 0 ? '1' : '0';
                  }
                  $ad['is_favouriate'] = $isFavourite;
                  $ad['is_sell']          = '1';
                  $ad['city_id']          = $adData->city_id;
                  $ad['city_are_id']      = $adData->city_area_id;
                  $ad['location']         = $adData->area->title_name .' ('.$adData->city->title_name .')';
                  $ad['ad_date']          = date('Y-m-d H:i:s',strtotime($adData->created_at));
                  $ad['seller_id']        = $adData->user_id;
                  $ad['seller_name']      = $adData->user->name;
                  $ad['seller_profile']   = $adData->user->profile_image;
                  $ad['seller_registration_date']   = date('Y-m-d',strtotime($adData->user->created_at));
                  $ad['seller_email']     = $adData->user->email;
                  $ad['seller_phone']     = $adData->user->phone;

               return ['status'=>true,'message'=> __('Record found') , 'data' => $ad ];
             }else{
               return ['status'=>false,'message'=> __('Record not not') ];
             }
    }

    public function adUpdate(Request $request){
       $input = $request->all();
       $rules = [
           'ad_id'             => 'required',
           'user_id'           => 'required',
           'category_id'       => 'required',
           'sub_category_id'   => 'required',
           'city_id'           => 'required',
           'city_area_id'      => 'required',
           'title'             => 'required',
           'description'       => 'required',
           'price'             => 'required',
           'condition'         => 'required',
        ];
      
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
           $errors =  $validator->errors()->all();
           return response(['status' => false , 'message' => $errors[0]]);              
        }

        $adData = [
          'category_id'     => $input['category_id'],
          'sub_category_id' => $input['sub_category_id'],
          'title'           => $input['title'],
          'description'     => $input['description'],
          'price'           => $input['price'],
          'city_id'         => $input['city_id'],
          'city_area_id'    => $input['city_area_id'],
          'user_id'         => $input['user_id'],
          'condition'       => $input['condition'],
          'brand'           => $input['brand'] ?? NULL
        ];
        DB::beginTransaction();
        try {
         DB::table('ads')->where('id',$input['ad_id'])->update($adData);
         $adId = $input['ad_id'];
         $imageData = array();
         if($request->hasFile('image')){
            foreach ($request->image as $key => $image) {
                $imageName = str_random('10').'.'.time().'.'.$image->getClientOriginalExtension();
                $image->move(public_path('images/ad/'), $imageName);
                array_push($imageData, ['ad_id' => $adId,'name' => $imageName]);
            }
         }       
          if($imageData)
              DB::table('ad_images')->insert($imageData);
          DB::commit();
          return ['status'=> true ,'message'=>__('Successfully updated ad')];
        } catch ( \Exception $e) {
          DB::rollback();
            return ['status'=> false ,'message'=> __('Failed to update ad') ];
        }
    }

    public function uploadAdImage(Request $request){

       $input = $request->all();
       $rules = [
           'image'     => 'required'
        ];
      
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
           $errors =  $validator->errors()->all();
           return response(['status' => false , 'message' => $errors[0]]);              
        }

        $fileName = null;
        if ($request->hasFile('image')) {
            $fileName = str_random('10').'.'.time().'.'.request()->image->getClientOriginalExtension();
            request()->image->move(public_path('images/ad/'), $fileName);
        }

        if($fileName){
          $data = [
              'name' => $fileName,
              'url'  => asset('images/ad/'.$fileName)
          ];
          return ['status'=>true,'message'=>__('Successfully uploaded image'),'data'=> $data];
        }else{
          return ['status'=>false,'message'=>__('Failed to upload image')];
        }

    }

    public function removeAdImage(Request $request){
       
       $input = $request->all();
       $rules = [
           'image_id'     => 'required',
           'ad_id'        => 'required'
        ];
      
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
           $errors =  $validator->errors()->all();
           return response(['status' => false , 'message' => $errors[0]]);              
        }

         if(DB::table('ad_images')->where('id',$input['image_id'])->where('ad_id',$input['ad_id'])->delete()){
          return ['status'=>true,'message'=>__('Successfully removed image')];
        }else{
          return ['status'=>false,'message'=>__('Failed to remove image')];
        }
    }

     public function getImage($ad_id){
        $Ad = Ad::find($ad_id);
        $imageData = DB::table('ad_images')->select('name')->where('ad_id',$ad_id)->first();
        if(!$imageData)
             return asset('public/backend/images/' . $imageData->name );
        else
             return asset('public/backend/images/image-not-found.jpg');
     }

     public function chatUsers(Request $request){

      $user_id = $request->user_id;

      $rules = [
        'user_id'        => 'required'
      ];

      $validator = Validator::make($request->all(), $rules);

     if ($validator->fails()) {
        $errors =  $validator->errors()->all();
        return response(['status' => false , 'message' => $errors[0]]);              
     }

        $userData = DB::select("SELECT t1.id as chat_id , t1.ad_id , IF(sender_id = $user_id , receiver_id , sender_id ) as user_id , users.name as user_name , users.profile_image , users.is_online , users.last_seen , ads.title , t1.message , ad_images.name as ad_image , t1.created_at as time FROM `chats` as t1 INNER JOIN (SELECT MAX(id) as max_id FROM `chats` GROUP BY ad_id) as t2 ON t1.id = t2.max_id JOIN `users` ON IF(sender_id = $user_id , receiver_id , sender_id ) = users.id JOIN `ads` ON t1.ad_id = ads.id JOIN `ad_images` ON t1.ad_id = ad_images.ad_id where (t1.sender_id = $user_id OR t1.receiver_id = $user_id)  AND  users.name like '%$request->search%' ORDER BY t2.max_id DESC");
       
      if($userData){
        $users = [];
        foreach($userData as $key => $value){
          $temp = array();
          $temp['chat_id']  = $value->chat_id;
          $temp['ad_id']    = $value->ad_id;
          $temp['message']  = $value->message;
          $temp['title']    = $value->title;
          $temp['ad_image'] = asset('public/images/ad/') .'/'. $value->ad_image;
          $temp['user_id']  = $value->user_id;
          $temp['user_name']  = $value->user_name;
          $temp['user_profile'] = asset('public/images/profile/') .'/'. $value->profile_image;
          $temp['is_online']  = $value->is_online;
          if(!empty($value->last_seen) && !is_null($value->last_seen)){
            if(strtotime(date('Y-m-d H:i:s')) > strtotime(date('Y-m-d H:i:s',strtotime($value->last_seen))) )
              $temp['last_seen']       =  date('Y-m-d h:i A',strtotime($value->last_seen));
            else
              $temp['last_seen']       =  date('h:i A',strtotime($value->last_seen));
          }else{
              $temp['last_seen']       = '';
          }

          if(strtotime(date('Y-m-d H:i:s')) > strtotime(date('Y-m-d H:i:s',strtotime($value->time))) )
            $temp['time']       =  date('Y-m-d h:i A',strtotime($value->time));
          else
            $temp['time']       =  date('h:i A',strtotime($value->time));
          array_push($users, $temp);
        }
        return ['status' => true ,'message'=>'User found' , 'data'=>$users];
      }
        return ['status' => false ,'message'=>'User not found'];
     }

     public function chatConversation(Request $request){
      
            $inputs = $request->all();
            $rules = [
              'ad_id'       => 'required',
              'sender_id'   => 'required',
              'receiver_id' => 'required'
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
              $errors =  $validator->errors()->all();
              return response(['status' => false , 'message' => $errors[0]]);              
            }

           $sender_id    = $inputs['sender_id'];
           $receiver_id  = $inputs['receiver_id'];
           $ad_id        = $inputs['ad_id'];

           $userData = DB::select("SELECT chats.ad_id, IF(chats.sender_id = $sender_id , receiver_id , sender_id ) as user_id, users.name as user_name, users.profile_image as user_profile, ads.title, users.is_online, users.last_seen, ad_images.name as ad_image FROM `chats` JOIN users ON IF(chats.sender_id = $sender_id , receiver_id , sender_id ) = users.id JOIN ads ON ads.id = chats.ad_id JOIN ad_images ON ad_images.ad_id = ads.id WHERE chats.ad_id = $ad_id AND ( chats.sender_id =$sender_id AND chats.receiver_id = $receiver_id) OR ( chats.sender_id = $receiver_id AND chats.receiver_id = $sender_id ) ORDER BY chats.id desc limit 1");

           $user = array();
           if($userData){
                $userData = $userData[0];
                $user['ad_id']      = $userData->ad_id;
                $user['title']      = $userData->title;
                $user['ad_image']   = asset('public/images/ad/') .'/'. $userData->ad_image;
                $user['user_id']    = $userData->user_id;
                $user['user_name']  = $userData->user_name;
                $user['user_profile'] = asset('public/images/profile/') .'/'. $userData->user_profile;
                $user['is_online']  = $userData->is_online;
                if(!empty($userData->last_seen) && !is_null($userData->last_seen)){
                  if(strtotime(date('Y-m-d H:i:s')) > strtotime(date('Y-m-d H:i:s',strtotime($userData->last_seen))) )
                    $user['last_seen']       =  date('Y-m-d h:i A',strtotime($userData->last_seen));
                  else
                    $user['last_seen']       =  date('h:i A',strtotime($userData->last_seen));
                }else{
                    $user['last_seen']       = '';
                }
                $user = (object) $user;
           }

              $dataData = DB::select("SELECT t1.* , senders.profile_image as sender_profile , receivers.profile_image  as receiver_profile , senders.name as sender_name , receivers.name as receiver_name FROM chats AS t1 INNER JOIN ( SELECT LEAST(sender_id, receiver_id) AS sender_id, GREATEST(sender_id, receiver_id) AS receiver_id FROM chats GROUP BY LEAST(sender_id, receiver_id), GREATEST(sender_id, receiver_id) ) AS t2 ON LEAST(t1.sender_id, t1.receiver_id) = t2.sender_id AND GREATEST(t1.sender_id, t1.receiver_id) = t2.receiver_id JOIN users AS senders ON senders.id = t1.sender_id JOIN users AS receivers ON receivers.id = t1.receiver_id JOIN ads ON ads.id = t1.ad_id WHERE t1.sender_id = $sender_id AND t1.receiver_id = $receiver_id OR t1.sender_id = $receiver_id AND t1.receiver_id = $sender_id");
              
              if($dataData){
                $users = array();
                foreach($dataData as $key => $value){
                  $temp = [];
                  $temp['chat_id']        = $value->id;
                  $temp['message']        = $value->message;
                  $temp['sender_id']      = $value->receiver_id;
                  $temp['sender_name']    = $value->sender_name;
                  $temp['sender_profile'] = asset('public/images/profile/') .'/'. $value->sender_profile;

                  $temp['receiver_id']      = $value->receiver_id;
                  $temp['receiver_name']    = $value->receiver_name;
                  $temp['receiver_profile'] = asset('public/images/profile/') .'/'. $value->receiver_profile;

                  if(strtotime(date('Y-m-d H:i:s')) > strtotime(date('Y-m-d H:i:s')))
                    $temp['message_time']       =  date('Y-m-d h:i A',strtotime($value->created_at));
                  else
                    $temp['message_time']       =  date('h:i A',strtotime($value->created_at));
                  array_push($users,$temp);
                }
                return ['status' => true ,'message'=>'User found' , 'user' => $user , 'data'=>$users];
              }
                return ['status' => false ,'message'=>'User not found'];
     }
}