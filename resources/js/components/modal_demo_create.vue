<template>
  <div class="w-100">
    <loading
      :active.sync="is_loading"
      :can-cancel="false"
      :is-full-page="true"
      :lock-scroll="true"
    ></loading>
    <table class="table">
      <tr>
        <th>
          <small><i>Click to expand</i></small>
        </th>
        <th>Kiểu thi</th>
        <th>Khu vực</th>
        <th>Phòng thi</th>
        <th>Loại thi</th>
        <th>Thời gian thi</th>
        <th>Môn thi</th>
        <th>Giám thị 1</th>
        <th>Giám thị 2</th>
        <th>SL min</th>
        <th>SL max</th>
        <th>SL hiện tại</th>
      </tr>
      <template v-for="(plan, index) in check_plans" >
        <tr class="table-success">
          <th>
            <button
              class="badge btn btn-warning white-text rounded-pill"
              @click="onclickToggle(plan.id)"
            >
              <p v-if="isChosenExpand(plan.id)">▼</p>
              <p v-else>▶</p>
            </button>
          </th>
          <th>{{ testOnlineOffline(plan.organize_type) }}</th>
          <th>{{ showAreaName(plan.area_id) }}</th>
          <th>
            {{ showRoomName(plan.room_id, plan.url, plan.organize_type) }}
          </th>
          <th>{{ plan.retest_type }}</th>
          <th>{{ plan.date }} {{ "slot " + plan.slot }}</th>
          <th>
            {{ plan.retest_subjects }}
          </th>
          <th>
            {{ plan.supervisor1 }}
          </th>
          <th>
            {{ plan.supervisor2 }}
          </th>
          <th>
            {{ plan.min }}
          </th>
          <th>
            {{ plan.max }}
          </th>
          <th>
            {{ plan.order_list_count }}
          </th>
        </tr>
        <tr
          v-show="isChosenExpand(plan.id)" class="table-warning"
        >
          <th>#</th>
          <th>MSSV</th>
          <th>-</th>
          <th>-</th>
          <th>Loại thi</th>
          <th>Thời gian thi</th>
          <th>Môn thi</th>
          <th>Ghi chú</th>
          <th>Trạng thái</th>
          <th>T/gian đăng ký</th>
          <th>Đổi lịch</th>
          <th>Submit</th>
        </tr>
        <tr
          v-show="isChosenExpand(plan.id)"
          v-for="(order, i) in plan.list_order"
          class="table-warning"
        >
          <td>{{ i + 1 }}</td>
          <td>{{ order.student_user_code }}</td>
          <td><hr /></td>
          <td><hr /></td>
          <th>{{ showTestTypeName(order.retest_type_id) }}</th>
          <td><hr /></td>
          <td>
            <b>{{ order.retest_subject }}</b>
          </td>
          <td>{{ order.student_note }}</td>
          <td>{{ order.status_name }}</td>
          <td>{{ formatDateTime(order.created_at) }}</td>
          <td>
            <select
              v-model="order.change_plan_id"
              class="form-control form-control-sm"
            >
              <template>
                <option value="" disabled>Chọn kế hoạch thi</option>
              </template>
              <template>
                <option value="-1">Xoá khỏi danh sách</option>
              </template>
              <template v-for="option_plan in check_plans">
                <option
                  :value="option_plan.id"
                  :key="option_plan.id"
                  v-if="
                    plan.id != option_plan.id &&
                    order.retest_type_id == option_plan.retest_type_id
                  "
                >
                  Thi {{ option_plan.retest_type }} - {{ option_plan.date }}
                  {{ "slot " + option_plan.slot }}
                </option>
              </template>
            </select>
          </td>
          <td>
            <button
              :disabled="
                order.change_plan_id == null ||
                order.change_plan_id == undefined
              "
              class="badge btn btn-warning white-text rounded-pill"
              @click="onlickChangeOrderPlan(order, order.change_plan_id, index)"
            >
              ⇆
            </button>
          </td>
        </tr>
      </template>
      <tr class="table-danger">
        <th>
          <button
            class="badge btn btn-warning white-text rounded-pill"
            @click="onclickToggle(-1)"
          >
            <p v-if="isChosenExpand(-1)">▼</p>
            <p v-else>▶</p>
          </button>
        </th>
        <th colspan="10" style="text-align: center">
          <h5 class="text-center font-weight-bold">
            Danh sách chưa được xếp lớp
          </h5>
          <div v-html="createStatisticText()"></div>
        </th>
        <th>
          {{ check_unset_orders.length }}
        </th>
      </tr>
      <tr v-show="isChosenExpand(-1)">
        <th colspan="12">
          <div class="row w-100">
            <div class="col-sm-2">
              <label for="input-search-student-code">Search MSV</label>
              <input
                v-model="filter_options_unset_orders.student_code"
                type="text"
                name="input-search-student-code"
                class="form-control"
              />
            </div>
            <div class="col-sm-2">
              <label for="input-search-subject-code">Search mã môn</label>
              <input
                v-model="filter_options_unset_orders.subject_code"
                type="text"
                name="input-search-subject-code"
                class="form-control"
              />
            </div>
            <div class="col-sm-2">
              <label for="select-search-retest-type">Loại thi</label>
              <b-form-select
                name="select-search-retest-type"
                :options="option_test_type_options"
                value-field="value"
                text-field="name"
                v-model="filter_options_unset_orders.retest_type_id"

              >
              </b-form-select>
            </div>
            <div class="col-sm-4">
              <label for="checkbox-search-duplicated-calendar"> </label>
              <!-- <b-form-checkbox
                v-model="filter_options_unset_orders.is_duplicated_calendar"
                name="checkbox-search-duplicated-calendar"
                switch
              >
                Lọc đơn đăng ký trùng lịch
              </b-form-checkbox> -->
            </div>
          </div>
        </th>
      </tr>
      <tr
        v-show="isChosenExpand(-1)"
        class="table-warning"
      >
        <th>#</th>
        <th>MSSV</th>
        <th><hr /></th>
        <th><hr /></th>
        <th>Loại thi</th>
        <th>Thời gian thi</th>
        <th>Môn thi</th>
        <th>Ghi chú</th>
        <th>Trạng thái</th>
        <th>T/gian đăng ký</th>
        <th>Đổi lịch</th>
        <th>Submit</th>
      </tr>
      <tr
        v-show="isChosenExpand(-1)"
        v-for="(order, index_unset_orders) in filtered_unset_orders"
        :key="'order-' + index_unset_orders"
        :class="
          order.duplicated_calendar.length > 0
            ? 'table-danger'
            : 'table-warning'
        "
      >
        <td>{{ index_unset_orders + 1 }}</td>
        <td>
          <b>{{ order.student_user_code }}</b>
        </td>
        <td><hr /></td>
        <td><hr /></td>
        <th>{{ showTestTypeName(order.retest_type_id) }}</th>
        <td><hr /></td>
        <td>
          <b>{{ order.retest_subject }}</b>
        </td>
        <td>{{ order.student_note }}</td>
        <td>{{ order.status_name }}</td>
        <td>{{ formatDateTime(order.created_at) }}</td>
        <td>
          <select
            v-model="order.change_plan_id"
            class="form-control form-control-sm"
          >
            <template>
              <option value="" disabled>Chọn kế hoạch thi</option>
            </template>
            <template v-for="option_plan in check_plans">
              <option
                :value="option_plan.id"
                :key="option_plan.id"
                v-if="order.retest_type_id == option_plan.retest_type_id"
              >
                Thi {{ option_plan.retest_type }} - {{ option_plan.date }}
                {{ "slot " + option_plan.slot }}
              </option>
            </template>
          </select>
          <div
            v-for="(schedule, index) in order.duplicated_calendar"
            :key="index"
          >
            <br />
            {{ schedule.date + " Slot " + schedule.slot }}
          </div>
        </td>
        <td>
          <button
            :disabled="
              order.change_plan_id == null || order.change_plan_id == undefined
            "
            class="badge btn btn-warning white-text rounded-pill"
            @click="onlickChangeOrderPlan(order, order.change_plan_id, -1)"
          >
            ⇆
          </button>
        </td>
      </tr>
    </table>
    <div class="row w-100 mt-2">
      <div class="col-sm-1"></div>
      <div class="col-sm-10">
        <button
          :disabled="isDisableSave"
          @click="onClickSavePlan()"
          class="btn btn-success w-100"
        >
          Lưu lịch
        </button>
      </div>
      <div class="col-sm-1"></div>
    </div>
  </div>
</template>

<script>
// Import component
import Loading from "vue-loading-overlay";
// Import stylesheet
import "vue-loading-overlay/dist/vue-loading.css";
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
  components: { Loading },
  props: {
    is_arrange_by_area: {
      type: Boolean,
      default: false,
    },
    choosen_area_id: {
      type: Number,
      default: null,
    },
    rooms: {
      type: Array,
      required: false,
      default: [],
    },
    areas: {
      type: Array,
      required: false,
      default: [],
    },
    available_service_id: {
      type: Number,
      require: true,
      default: 0,
    },
    plans: {
      type: Array,
      require: true,
      default: [],
    },
    unset_orders: {
      type: Array,
      require: true,
      default: [],
    },
    statistic_unset_orders: {
      type: Object,
      require: true,
    },
  },
  data() {
    return {
      is_loading: false,
      isDisableSave: false,
      check_plans: [],
      check_unset_orders: [],
      expanded_list: [],
      test_type_option: test_type_option,
      option_test_type_options: [
        ...[
          {
            name: "Tất cả",
            value: 0,
          },
        ],
        ...test_type_option,
      ],
      filter_options_unset_orders: {
        student_code: "",
        is_duplicated_calendar: false,
        subject_code: "",
        retest_type_id: 0,
      },
      filtered_unset_orders: [],
    };
  },
  watch: {
    filter_options_unset_orders_changed() {
      this.filterUnsetOrders();
    },
    plans(newVal, oldVal) {
      this.check_plans = newVal;
    },
    unset_orders(newVal, oldVal) {
      this.check_unset_orders = newVal;
      this.filtered_unset_orders = newVal;
    },
  },
  computed: {
    filter_options_unset_orders_changed() {
      return JSON.stringify(this.filter_options_unset_orders);
    },
  },
  methods: {
    filterUnsetOrders() {
      var filter_options = this.filter_options_unset_orders;
      this.filtered_unset_orders = this.check_unset_orders.filter((order) => {
        return (
          (filter_options.student_code == "" ||
          filter_options.student_code == null
            ? true
            : order.student_user_code.includes(filter_options.student_code)) &&
          (filter_options.is_duplicated_calendar == true
            ? order.duplicated_calendar.length > 0
            : true) &&
          (filter_options.subject_code == "" ||
          filter_options.subject_code == null
            ? true
            : order.retest_subject.includes(filter_options.subject_code)) &&
          (filter_options.retest_type_id == 0
            ? true
            : order.retest_type_id == filter_options.retest_type_id)
        );
      });
    },
    onClickSavePlan() {
      if (confirm("Chốt kế hoạch thi?")) {
        this.isDisableSave = true;
        let data = {
          available_service_id: this.available_service_id,
          plan_data: this.check_plans,
          is_arrange_by_area: this.is_arrange_by_area,
          choosen_area_id: this.choosen_area_id,
        };
        this.is_loading = true;
        this.postSaveRetestPlan(data);
      }
    },
    postSaveRetestPlan(data) {
      this.is_loading = true;
      axios.post("/api/postSaveRetestPlan", data).then((res) => {
        alert("Lưu thành công!");
        this.is_loading = false;
        window.location.reload();
      });
    },
    testOnlineOffline(key) {
      switch (key) {
        case 0:
          return "Online";
        case 1:
          return "Offline";
        default:
          return "Undifined";
      }
    },
    showTestTypeName(key) {
      let type = this.test_type_option.find((e) => {
        return parseInt(e.value) == parseInt(key);
      });
      return type.name;
    },
    showRoomName(key, url, is_offline_area) {
      if (is_offline_area) {
        let room = this.rooms.find((e) => {
          return parseInt(e.id) == parseInt(key);
        });
        return room.room_name;
      } else {
        return url;
      }
    },
    showAreaName(key) {
      let area = this.areas.find((e) => {
        return parseInt(e.id) == parseInt(key);
      });
      return area.area_name;
    },
    onclickToggle(id) {
      if (this.expanded_list.includes(id)) {
        const index = this.expanded_list.indexOf(id);
        if (index > -1) {
          this.expanded_list.splice(index, 1);
        }
      } else {
        this.expanded_list.push(id);
      }
    },
    isChosenExpand(id) {
      return this.expanded_list.includes(id);
    },
    onlickChangeOrderPlan(order, plan_id, current_plan_index) {
      if (plan_id == -1) {
        if (
          confirm(
            `Chuyển đơn thi lại sinh viên ${order.student_user_code} sang danh sách không được xếp lớp`
          )
        ) {
          this.changeToUnsetOrderList(order, current_plan_index);
        }
      } else {
        const index = this.check_plans.findIndex((plan) => plan.id == plan_id);
        if (
          confirm(
            `Chuyển đơn thi lại sinh viên ${
              order.student_user_code
            } sang kế hoạch thi số ${index + 1}`
          )
        ) {
          this.changeOrderPlan(order, index, current_plan_index);
        }
      }
    },
    changeToUnsetOrderList(order, current_plan_index) {
      this.check_plans[current_plan_index]["list_order"] = this.check_plans[
        current_plan_index
      ]["list_order"].filter(function (ord) {
        return ord.id != order.id;
      });
      order.change_plan_id = null;
      let new_order = { ...order };
      this.check_unset_orders.push(new_order);
    },
    changeOrderPlan(order, new_plan_index, current_plan_index) {
      if (current_plan_index >= 0) {
        this.check_plans[current_plan_index]["list_order"] = this.check_plans[
          current_plan_index
        ]["list_order"].filter(function (ord) {
          return ord.id != order.id;
        });
      } else {
        this.check_unset_orders = this.check_unset_orders.filter(function (
          ord
        ) {
          return ord.id != order.id;
        });
      }

      order.change_plan_id = null;
      let new_order = { ...order };
      this.check_plans[new_plan_index]["list_order"].push(new_order);
    },
    createStatisticText() {
      let raw = this.calculateStatisticUnsetOrder();
      var text = ``;
      test_type_option.forEach((test_type) => {
        if (
          raw[test_type.value].Total == undefined ||
          raw[test_type.value].Total == null ||
          raw[test_type.value].Total <= 0
        ) {
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
      });
      return text;
    },
    calculateStatisticUnsetOrder() {
      let eos_orders = {};
      eos_orders.Total = 0;
      let oral_orders = {};
      oral_orders.Total = 0;
      let practice_orders = {};
      practice_orders.Total = 0;
      this.check_unset_orders.forEach((order) => {
        switch (order.retest_type_id) {
          case 1:
            eos_orders.Total++;
            this.checkSubjectCodeOrder(eos_orders, order.retest_subject);
            break;
          case 2:
            oral_orders.Total++;
            this.checkSubjectCodeOrder(oral_orders, order.retest_subject);
            break;
          case 3:
            practice_orders.Total++;
            this.checkSubjectCodeOrder(practice_orders, order.retest_subject);
            break;
          default:
            break;
        }
      });
      return {
        1: eos_orders,
        2: oral_orders,
        3: practice_orders,
      };
    },
    checkSubjectCodeOrder(order, retest_subject) {
      if (order.hasOwnProperty(retest_subject)) {
        order[retest_subject]++;
      } else {
        order[retest_subject] = 1;
      }
    },
    formatDateTime(date) {
      return moment(date).format('YYYY-MM-DD HH:mm:ss');
    },
  },
};
</script>

<style>
</style>