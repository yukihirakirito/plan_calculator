<template>
    <div class="w-100">
      <loading
        :active.sync="is_loading"
        :can-cancel="false"
        :is-full-page="true"
        :lock-scroll="true"
      ></loading>
      <div class="card w-100">
        <div class="card-body">
          <form enctype="multipart/form-data" class="" onsubmit="return false">
            <div class="form-group w-100">
              <div class="row">
                <div class="col-sm-6">
                  <label for="slt-available-">Đợt thi</label>
                  <select
                    v-model="submitData.available_service_id"
                    class="form-control"
                    id="slt-subject"
                    required
                  >
                    <option selected></option>
                    <option
                      v-for="(option, id) in available_service_id_list"
                      :key="id"
                      :value="option.id"
                    >
                      {{ option.name }}
                    </option>
                  </select>
                </div>
                <div class="col-sm-3">
                  <label for="arrage-by-area">Xếp lớp theo khu vực</label>
                  <select
                    v-model="submitData.is_arrange_by_area"
                    class="form-control"
                  >
                    <option selected :value="false">Không</option>
                    <option :value="true">Có</option>
                  </select>
                </div>
                <div class="col-sm-3" v-show="submitData.is_arrange_by_area">
                  <label for="arrage-by-area">Chọn khu vực</label>
                  <select
                    v-model="submitData.choosen_area_id"
                    class="form-control"
                    id="slt-subject"
                    required
                    @change="changeChoosenArea()"
                  >
                    <option
                      v-for="(area, index) in filtered_area_list"
                      :key="index"
                      selected
                      :value="area.id"
                    >
                      {{ area.area_name }}
                    </option>
                  </select>
                </div>
              </div>
            </div>
            <hr />
            <div v-html="createStatisticText(statisticAllOrders)"></div>
            <hr />
  
            <table class="table table-hover table-striped table-sm table-info">
              <thead>
                <tr class="table-warning font-weight-bold text-uppercase">
                  <th scope="col" style="width: 2%">#</th>
                  <th scope="col" style="width: 7%">Hình thức tổ chức</th>
                  <th scope="col" style="width: 12%">Ngày</th>
                  <th scope="col" style="width: 6%">Ca</th>
                  <th scope="col" style="width: 6%">Thời gian</th>
                  <th scope="col" style="width: 10%">Khu vực thi</th>
                  <th scope="col" style="width: 10%">Phòng thi</th>
                  <th scope="col" style="width: 8%">Loại thi</th>
                  <th scope="col" style="width: 15%">Môn thi (Mã gốc)</th>
                  <th scope="col" style="width: 4%">SL Min</th>
                  <th scope="col" style="width: 4%">SL Max</th>
                  <th scope="col" style="width: 7%">Giám thị 1</th>
                  <th scope="col" style="width: 7%">Giám thị 2</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, num) in submitData.plan_data" :key="num">
                  <th scope="row" name="ID" style="width: 2%">
                    <button
                      @click="onClickRemoveRow(num)"
                      class="badge btn btn-warning white-text rounded-pill"
                    >
                      ❌
                    </button>
                  </th>
                  <th scope="row" name="organize_type" style="width: 7%">
                    {{ getOrganizeType(row.organize_type) }}
                  </th>
                  <th scope="row" name="Date" style="width: 12%">
                    {{ row.date }}
                  </th>
                  <th scope="row" name="Time" style="width: 6%">
                    {{ row.slot }}
                  </th>
                  <th scope="row" name="RoomType" style="width: 6%">
                    {{ getTimeFromSlotId(row.slot) }}
                  </th>
                  <th scope="row" name="Room" style="width: 10%">
                    {{ row.area.area_name }}
                  </th>
                  <th scope="row" name="TestType" style="width: 10%">
                    {{ row.room.room_name }}
                    <div v-if="row.organize_type == 0" class="w-100">
                      {{ row.url }}
                    </div>
                  </th>
                  <th scope="row" name="TestType" style="width: 8%">
                    {{ showTestTypeName(row.test_type) }}
                  </th>
                  <th scope="row" style="width: 22%">
                    {{ showListSubjectCode(row.skill_code) }}
                  </th>
                  <th scope="row" style="width: 5%">{{ row.min_number }}</th>
                  <th scope="row" style="width: 5%">{{ row.max_number }}</th>
                  <th scope="row" style="width: 7%">{{ row.supervisor1 }}</th>
                  <th scope="row" style="width: 7%">{{ row.supervisor2 }}</th>
                </tr>
                <tr>
                  <th scope="row" name="ID" style="width: 2%">
                    <button
                      @click="onClickAddPlan()"
                      class="badge btn btn-success white-text rounded-pill"
                    >
                      ✚
                    </button>
                  </th>
                  <th scope="row" name="organize_type" style="width: 7%">
                    <select
                      :disabled="submitData.is_arrange_by_area"
                      class="form-control form-control-sm"
                      v-model="plan.organize_type"
                    >
                      <option :value="parseInt(0)">Online</option>
                      <option :value="parseInt(1)">Offline</option>
                    </select>
                  </th>
                  <th scope="row" style="width: 12%">
                    <input
                      id="inputDate"
                      type="date"
                      class="form-control form-control-sm"
                      placeholder="Ngày thi"
                      aria-label="Ngày thi"
                      v-model="plan.date"
                    />
                    <button
                      type="button"
                      class="close"
                      aria-label="Close"
                      @click="plan.date = null"
                    >
                      <span aria-hidden="true">&times;</span>
                    </button>
                  </th>
                  <th scope="row" style="width: 4%">
                    <div class="w-100">
                      <select
                        class="form-control form-control-sm"
                        v-model="plan.slot"
                      >
                        <option :value="null" selected>Not Select</option>
                        <option
                          v-for="(slot, index) in slot_list"
                          :key="index"
                          :value="slot.id"
                        >
                          {{ slot.id }}
                        </option>
                      </select>
                    </div>
                  </th>
                  <th scope="row" style="width: 6%">
                    <small class="font-weight-bold">{{
                      getTimeFromSlotId(plan.slot)
                    }}</small>
                  </th>
                  <th scope="row" style="width: 10%">
                    <div class="w-100">
                      <multiselect
                        :disabled="submitData.is_arrange_by_area == true"
                        style="font-size: 12px"
                        v-model="plan.area"
                        :options="filtered_area_list"
                        :multiple="false"
                        track-by="area_name"
                        label="area_name"
                        :taggable="true"
                        :searchable="true"
                      >
                        <template slot="singleLabel" slot-scope="{ option }">
                          <span style="font-size: 11px">{{
                            option.area_name
                          }}</span>
                        </template>
                      </multiselect>
                    </div>
                  </th>
                  <th scope="row" style="width: 10%">
                    <div class="w-100">
                      <multiselect
                        style="font-size: 12px"
                        v-model="plan.room"
                        :options="filtered_room_list"
                        :multiple="false"
                        track-by="room_name"
                        label="room_name"
                        :taggable="true"
                        :searchable="true"
                      >
                        <template slot="singleLabel" slot-scope="{ option }">
                          <span style="font-size: 11px">{{
                            option.room_name
                          }}</span>
                        </template>
                      </multiselect>
                      <input
                        v-model="plan.url"
                        v-if="plan.organize_type == 0"
                        class="form-control w-100 mt-2"
                        placeholder="https://"
                        type="text"
                      />
                    </div>
                  </th>
                  <th scope="row" name="TestType" style="width: 8%">
                    <div class="w-100">
                      <select
                        name="inputArea"
                        id="inputArea"
                        class="form-control form-control-sm"
                        v-model="plan.test_type"
                      >
                        <option
                          v-for="(type, index) in test_type_option"
                          :key="index"
                          :value="type.value"
                        >
                          {{ type.name }}
                        </option>
                      </select>
                    </div>
                  </th>
                  <th scope="row" style="width: 15%">
                    <multiselect
                      v-model="plan.skill_code"
                      tag-placeholder="Search"
                      placeholder="Search"
                      label="name"
                      track-by="code"
                      :allow-empty="false"
                      :options="subject_list"
                      :multiple="true"
                      :taggable="true"
                      @tag="addTag"
                    >
                    </multiselect>
                  </th>
                  <th scope="row" style="width: 5%">
                    <input
                      v-model="plan.min_number"
                      type="text"
                      class="form-control form-control-sm"
                    />
                  </th>
                  <th scope="row" style="width: 5%">
                    <input
                      v-model="plan.max_number"
                      type="text"
                      class="form-control form-control-sm"
                    />
                  </th>
                  <th scope="row" style="width: 7%">
                    <input
                      type="text"
                      disabled
                      name="supervisor1"
                      id=""
                      v-model="plan.supervisor1"
                      class="form-control form-control-sm"
                    />
                    <input
                      placeholder="Search user"
                      v-model="key_search_supervisor1"
                      type="text"
                      class="form-control form-control-sm"
                    />
                    <button
                      type="button"
                      class="close"
                      aria-label="Close"
                      @click="clearSupervisor1()"
                    >
                      <span aria-hidden="true">&times;</span>
                    </button>
                    <ul class="list-group list-group-flush">
                      <li
                        v-for="(user_login1, index_user_login1) in supervisor1_list"
                        :key="index_user_login1"
                        class="
                          list-group-item
                          d-flex
                          justify-content-between
                          align-items-center
                        "
                      >
                        {{ user_login1 }}
                        <button
                          @click="onChangeUserLogin1(user_login1)"
                          class="badge btn btn-success white-text rounded-pill"
                        >
                          ►
                        </button>
                      </li>
                    </ul>
                  </th>
                  <th scope="row" style="width: 7%">
                    <input
                      type="text"
                      disabled
                      name="supervisor2"
                      id=""
                      v-model="plan.supervisor2"
                      class="form-control form-control-sm"
                    />
                    <input
                      placeholder="Search user"
                      v-model="key_search_supervisor2"
                      type="text"
                      class="form-control form-control-sm"
                    />
                    <button
                      type="button"
                      class="close"
                      aria-label="Close"
                      @click="clearSupervisor2()"
                    >
                      <span aria-hidden="true">&times;</span>
                    </button>
                    <ul class="list-group list-group-flush">
                      <li
                        v-for="(user_login2, index_user_login2) in supervisor2_list"
                        :key="index_user_login2"
                        class="
                          list-group-item
                          d-flex
                          justify-content-between
                          align-items-center
                        "
                      >
                        {{ user_login2 }}
                        <button
                          @click="onChangeUserLogin2(user_login2)"
                          class="badge btn btn-success white-text rounded-pill"
                        >
                          ►
                        </button>
                      </li>
                    </ul>
                  </th>
                </tr>
              </tbody>
            </table>
            <div class="row">
              <div class="col-sm-2"></div>
              <div class="col-sm-8">
                <div class="form-group w-100">
                  <button
                    :disabled="
                      this.submitData.plan_data.length == 0 ||
                      this.submitData.available_service_id == null
                    "
                    @click="onclickCreateRetestPlan()"
                    class="btn btn-success w-100"
                  >
                    Xếp lớp thi lại
                  </button>
                </div>
              </div>
              <div class="col-sm-2"></div>
            </div>
          </form>
        </div>
      </div>
  
      <div
        class="modal fade"
        id="demoPlanModal"
        tabindex="-1"
        role="dialog"
        aria-labelledby="demoPlanModalLabel"
        aria-hidden="true"
      >
        <div class="modal-dialog modal-xl" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5>Kế hoạch thi</h5>
              <button
                type="button"
                class="close"
                data-dismiss="modal"
                aria-label="Close"
              >
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <demo-plan
                :ref="key_demo_plan"
                :available_service_id="submitData.available_service_id"
                :plans="dataDemoPlans"
                :unset_orders="dataUnsetOrders"
                :statistic_unset_orders="statisticUnsetOrders"
                :rooms="room_list"
                :areas="area_list"
                :is_arrange_by_area="is_arrange_by_area"
                :choosen_area_id="submitData.choosen_area_id"
              ></demo-plan>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  // Import component
  import Loading from "vue-loading-overlay";
  // Import stylesheet
  import "vue-loading-overlay/dist/vue-loading.css";
  import demoPlan from "./modal_demo_create.vue";
  import "vue-multiselect/dist/vue-multiselect.min.css";
  import Multiselect from "vue-multiselect";
  const default_plan = {
    organize_type: null,
    date: null,
    slot: null,
    room_type: "",
    room_value: "",
    skill_code: [],
    lesson_number: 0,
    test_type: null,
    supervisor1: null,
    supervisor2: null,
    min_number: 0,
    max_number: 0,
  };
  const test_type_option = [
    {
      name: "Thi EOS",
      value: 1,
    },
    {
      name: "Oral test",
      value: 2,
    },
    {
      name: "Thực hành",
      value: 3,
    },
  ];
  export default {
    components: { Multiselect, demoPlan, Loading },
    data() {
      return {
        key_demo_plan: 0,
        is_loading: false,
        submitData: {
          available_service_id: null,
          plan_data: [],
          is_arrange_by_area: false,
          choosen_area_id: null,
          choosen_area: null,
        },
        value: [],
        options: [
          { name: "ENT1123", code: "vu" },
          { name: "ENT1333", code: "js" },
          { name: "ENT1555", code: "os" },
        ],
        plan: {
          url: "",
          area: null,
          room: null,
          organize_type: null,
          date: null,
          slot: null,
          room_type: "",
          room_value: "",
          skill_code: [],
          lesson_number: 0,
          test_type: null,
          supervisor1: null,
          supervisor2: null,
          min_number: 1,
          max_number: 15,
        },
        available_service_id_list: [],
        area_list: [],
        filtered_area_list: [],
        room_list: [],
        filtered_room_list: [],
        subject_list: [],
        slot_list: [],
        test_type_list: [],
        supervisor1_list: [],
        key_search_supervisor1: "",
        supervisor2_list: [],
        key_search_supervisor2: "",
        test_type_option: test_type_option,
        dataDemoPlans: [],
        dataUnsetOrders: [],
        statisticUnsetOrders: {},
        statisticAllOrders: {},
      };
    },
    created() {
      this.is_loading = true;
      this.getPlanCreatorInformation();
      // this.is_loading = false;
    },
    computed: {
      is_arrange_by_area() {
        return this.submitData.is_arrange_by_area;
      },
      choosen_area_id() {
        return this.submitData.choosen_area_id;
      },
      plan_test_type() {
        return this.plan.test_type;
      },
      area_id() {
        return this.plan.area == null ? null : this.plan.area.id;
      },
      organize_type() {
        return this.plan.organize_type;
      },
      plan_room(){
        return JSON.stringify(this.plan.room);
      }
    },
    watch: {
      plan_room(){
        this.checkIfRoomBeScheduled();
      },
      choosen_area_id(new_value, old_value) {
        if (
          new_value != old_value &&
          this.submitData.is_arrange_by_area == true
        ) {
          var choosen_area_id = new_value;
          this.filtered_room_list = this.room_list.filter(function (room) {
            return room.area_id == choosen_area_id;
          });
          if (old_value == null || old_value == undefined) {
            return;
          }
          this.submitData.plan_data = [];
          this.plan.area = this.area_list.find((area) => area.id == new_value);
          this.submitData.choosen_area = this.plan.area;
        }
      },
      is_arrange_by_area(new_value, old_value) {
        if (new_value != old_value) {
          if (new_value) {
            this.plan.organize_type = 1;
            this.filtered_area_list = this.area_list.filter(function (area) {
              return area.is_offline_area == 1;
            });
          }
        }
      },
      organize_type(new_value, old_value) {
        var organize_type = new_value;
        if (
          new_value != old_value &&
          new_value != null &&
          this.submitData.is_arrange_by_area == false
        ) {
          this.filtered_area_list = this.area_list.filter(function (area) {
            return (
              organize_type ==
              parseInt(area.is_offline_area == null ? 0 : area.is_offline_area)
            );
          });
          this.plan.room = null;
        }
      },
      area_id(new_value, old_value) {
        if (
          new_value != old_value &&
          this.submitData.is_arrange_by_area == false
        ) {
          var choosen_area_id = new_value;
          this.filtered_room_list = this.room_list.filter(function (room) {
            return room.area_id == choosen_area_id;
          });
        }
      },
      plan_test_type(new_value, old_value) {
        if (new_value != "" && new_value != old_value && new_value != null) {
          switch (parseInt(new_value)) {
            case 1:
              this.plan.max_number = 25;
              break;
            case 2:
              this.plan.max_number = 13;
              break;
            case 3:
              this.plan.max_number = 20;
              break;
            default:
              break;
          }
        }
      },
      key_search_supervisor1(new_value, old_value) {
        this.supervisor1_list = [];
        if (new_value != "" && new_value != old_value && new_value != null) {
          this.searchUserLogin(new_value).then((res) => {
            this.supervisor1_list = res;
          });
        }
      },
      key_search_supervisor2(new_value, old_value) {
        this.supervisor2_list = [];
        if (new_value != "" && new_value != old_value && new_value != null) {
          this.searchUserLogin(new_value).then((res) => {
            this.supervisor2_list = res;
          });
        }
      },
    },
    methods: {
      checkIfRoomBeScheduled(){
        axios.get('/api/v1/getCheckDuplicateRoom',{
          params: {
            room_id: this.plan.room.id,
            date: this.plan.date,
            slot: this.plan.slot
          }
        }).then(res => {
          if (res) {
            alert(`Phòng ${this.plan.room.room_name} đã có lịch học/thi trong ngày ${this.plan.date} slot ${this.plan.slot} `);
          }
        })
      },
      changeChoosenArea() {
        var choosen_area_id = this.submitData.choosen_area_id;
        this.filtered_room_list = this.room_list.filter(function (room) {
          return room.area_id == choosen_area_id;
        });
        this.submitData.plan_data = [];
        this.plan.area = this.area_list.find(
          (area) => area.id == choosen_area_id
        );
        this.submitData.choosen_area = this.plan.area;
      },
      getOrganizeType(key) {
        switch (key) {
          case 0:
            return "Thi online";
          case 1:
            return "Thi Offline";
          default:
            return "Không thể xác định giá trị " + key;
        }
      },
      onclickCreateRetestPlan() {
        if(this.checkBackupTime()){
          alert('Hệ thống sẽ tắt chức năng "Xếp lớp thi lại" từ 8:00:00-9:00:00 và 16:00:00-17:00:00 hằng ngày.  \nXin lỗi mọi người vì sự bất tiện này!');
          return;
        }
        let data = [];
        for (let index = 0; index < this.submitData.plan_data.length; index++) {
          data.push({
            id: index + 1,
            date: this.submitData.plan_data[index]["date"],
            slot_id: this.submitData.plan_data[index]["slot"],
            skill_code: this.submitData.plan_data[index]["skill_code"].map(
              (e) => {
                return e.code;
              }
            ),
            retest_type_id: this.submitData.plan_data[index]["test_type"],
            min_number: this.submitData.plan_data[index]["min_number"],
            max_number: this.submitData.plan_data[index]["max_number"],
            supervisor1: this.submitData.plan_data[index]["supervisor1"],
            supervisor2: this.submitData.plan_data[index]["supervisor2"],
            area_id: this.submitData.plan_data[index]["area"]["id"],
            room_id: this.submitData.plan_data[index]["room"]["id"],
            url: this.submitData.plan_data[index]["url"],
            organize_type: this.submitData.plan_data[index]["organize_type"],
          });
        }
        this.postCreateRetestPlan({
          is_arrange_by_area: this.submitData.is_arrange_by_area,
          choosen_area_id: this.submitData.choosen_area_id,
          plans: data,
        });
      },
      checkBackupTime(){
        let current = new Date;
        let current_time  = current.getHours() + ":" + current.getMinutes() + ":" + current.getSeconds();
        if(Date.parse(`01/01/2011 ${current_time}`)  >= Date.parse(`01/01/2011 8:00:00`) && Date.parse(`01/01/2011 ${current_time}`) < Date.parse(`01/01/2011 9:00:00`)){
          return true;
        }
        else if(Date.parse(`01/01/2011 ${current_time}`)  >= Date.parse(`01/01/2011 16:00:00`) && Date.parse(`01/01/2011 ${current_time}`) < Date.parse(`01/01/2011 17:00:00`)){
          return true;
        }
        return false;
      },
      postCreateRetestPlan(data) {
        this.is_loading = true;
        axios.post("/api/v1/postCreateRetestPlan", data).then((res) => {
          this.key_demo_plan++;
          this.dataUnsetOrders = res.unset_orders;
          this.dataDemoPlans = res.plans;
          this.statisticUnsetOrders = res.orders_statistic;
          $("#demoPlanModal").modal("show");
          this.is_loading = false;
        });
      },
      getTempPlanData() {
        this.is_loading = true;
        axios.get("/api/v1/getTempPlanData").then((res) => {
          if (res.data != 0) {
            let _temp = JSON.parse(res.data);
            if (
              typeof _temp.available_service_id == "undefined" ||
              typeof _temp.plan_data == "undefined"
            ) {
              this.is_loading = false;
              return;
            } else {
              this.submitData = _temp;
            }
            var choosen_area_id = this.submitData.choosen_area_id;
            this.plan.area = this.area_list.find(
              (area) => area.id == choosen_area_id
            );
            this.filtered_room_list = this.room_list.filter(function (room) {
              return room.area_id == choosen_area_id;
            });
          }
          this.is_loading = false;
        });
      },
      saveDraft() {
        let data = JSON.stringify(this.submitData);
        this.storeTempPlanData(data);
      },
      storeTempPlanData(data) {
        this.is_loading = true;
        axios.post("/api/v1/storeTempPlanData", { data: data }).then((res) => {
          if (parseInt(res) == 0) {
            alert(
              "Chức năng tự động lưu dữ liệu gặp vấn đề.\nBạn có thể tiếp tục thực hiện tạo kế hoạch nhưng khi thoát trình duyệt sẽ bị mất kế hoạch!"
            );
          }
          this.is_loading = false;
        });
      },
      onClickRemoveRow(index) {
        this.removeRow(index);
      },
      removeRow(index) {
        if (index > -1) {
          this.submitData.plan_data.splice(index, 1);
        }
        this.saveDraft();
      },
      showAreaName(key) {
        let area = this.area_list.find((e) => {
          return parseInt(e.id) == parseInt(key);
        });
        return area.area_name;
      },
      showTestTypeName(key) {
        let type = this.test_type_option.find((e) => {
          return parseInt(e.value) == parseInt(key);
        });
        return type.name;
      },
      showListSubjectCode(raw) {
        let str = "";
        raw.forEach((subject) => {
          str += subject.code + ", ";
        });
        return str;
      },
      isPlanValid() {
        return (
          this.submitData.available_service_id > 0 &&
          this.plan.date != null &&
          this.plan.slot != null &&
          this.plan.area.id > 0 &&
          this.plan.room.id > 0 &&
          // (this.plan.organize_type == 0 ? (this.plan.url != null && this.plan.url != "") : true ) &&
          this.plan.skill_code.length > 0 &&
          this.plan.test_type > 0 &&
          this.plan.supervisor1 != null &&
          this.plan.supervisor1 != "" &&
          this.plan.min_number > 0 &&
          this.plan.max_number >= this.plan.max_number
        );
      },
      onClickAddPlan() {
        if (this.isPlanValid()) {
          let plan = this.plan;
          // plan.area_id = parseInt(area.id);
          // plan.room_id = parseInt(plan.room.id);
          this.addPlan(this.plan);
          let empty_plan = { ...default_plan };
          if (this.submitData.is_arrange_by_area) {
            empty_plan.organize_type = 1;
            var choosen_area_id = this.submitData.choosen_area_id;
            empty_plan.area = this.area_list.find(
              (area) => area.id == choosen_area_id
            );
          }
          this.plan = { ...empty_plan };
        } else {
          alert("Dữ liệu tạo kế hoạch không hợp lệ!");
        }
      },
      addPlan(data) {
        const row = { ...data };
        this.submitData.plan_data.push(row);
        this.saveDraft();
      },
      clearSupervisor1() {
        this.key_search_supervisor1 = "";
        this.plan.supervisor1 = "";
        this.supervisor1_list = [];
      },
      clearSupervisor2() {
        this.key_search_supervisor2 = "";
        this.plan.supervisor2 = "";
        this.supervisor2_list = [];
      },
      onChangeUserLogin1(key) {
        this.clearSupervisor1();
        this.plan.supervisor1 = key;
      },
      onChangeUserLogin2(key) {
        this.clearSupervisor2();
        this.plan.supervisor2 = key;
      },
      async searchUserLogin(key) {
        this.is_loading = true;
        let result = await axios.get("/api/v1/searchUserLogin", {
          params: { key: key },
        });
        this.is_loading = false;
        return result;
      },
      addTag(newTag) {
        const tag = {
          name: newTag,
          code: newTag.substring(0, 2) + Math.floor(Math.random() * 10000000),
        };
        this.options.push(tag);
        this.value.push(tag);
      },
      getPlanCreatorInformation() {
        this.is_loading = true;
        axios.get("/api/v1/getPlanCreatorInformation").then((res) => {
          this.available_service_id_list = this.getAvailableServiceId(
            res.available_service_id_list
          );
          this.slot_list = this.getSlotList(res.slot_list);
          this.area_list = res.area_list;
          this.room_list = res.room_list;
          this.subject_list = this.getSubjectList(res.subject_list);
          this.statisticAllOrders = res.orders_statistic;
          this.getTempPlanData();
          this.is_loading = false;
        });
      },
      getAvailableServiceId(raw) {
        let new_list = raw.map((e) => {
          return {
            id: e.available_services_id,
            name:
              e.available_services_name +
              " (" +
              moment(e.available_services_datetime_from).format("DD/MM/YY") +
              " - " +
              moment(e.available_services_datetime_from).format("DD/MM/YY") +
              ")" +
              " - Kỳ " +
              e.term_name,
          };
        });
        return new_list;
      },
      getNameTypeTest(value) {
        let type = this.test_type_option.find((e) => {
          return e.value == value;
        });
        return type.name;
      },
      getSlotList(raw) {
        let new_list = raw.map((e) => {
          return {
            id: e.slot_id,
            time:
              moment(e.slot_start, "HH:mm:ss").format("HH:mm") +
              " - " +
              moment(e.slot_end, "HH:mm:ss").format("HH:mm"),
          };
        });
        return new_list;
      },
      getTimeFromSlotId(id) {
        let valid = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
        if (valid.includes(id)) {
          let slot = this.slot_list.find((e) => {
            return e.id == id;
          });
          return slot.time;
        }
  
        return "";
      },
      getSubjectList(raw) {
        let new_list = [];
        raw.forEach((e) => {
          new_list.push({
            code: e,
            name: e
          });
        });
  
        return new_list;
      },
      createStatisticText(raw) {
        var text = ``;
        test_type_option.forEach((test_type) => {
          try {
            if ("Total" in raw[test_type.value] == false) {
              return;
            }
            text += `<b style="color:red"> ${test_type.name} (${
              raw[test_type.value].Total
            } đơn): </b> `;
            for (const [key, value] of Object.entries(raw[test_type.value])) {
              if (key != "Total") {
                text += `<span style="color:blue">${key}</span>(${value} đơn),  `;
              }
            }
            text += `<br>`;
          } catch (error) {
          }
        });
        return text;
      },
    },
  };
  </script>
  
  <style lang="css" scoped>
  table {
    overflow: auto;
  }
  .multiselect,
  .multiselect__select,
  .multiselect__tags,
  .multiselect__tags.span,
  .multiselect__tags {
    font-size: 12px !important;
  }
  .multiselect__tags {
    padding: 4px 40px 0 4px !important;
    font-size: 12px !important;
  }
  .multiselect,
  .multiselect__input,
  .multiselect__single {
    font-size: 12px !important;
  }
  </style>