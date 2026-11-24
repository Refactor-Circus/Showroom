import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { A as t, C as n, D as r, O as i, d as a, f as o, j as s } from "./catalog-DCoO3A8a.js";
import { h as c } from "./structure-DbqcM-iO.js";
import { AtriumErrorCard as ee, AtriumExtension as l, AtriumIntegrationScreen as te, AtriumNoAccess as ne, AtriumPageHeader as re, AtriumState as ie, atriumStatus as u, integrationData as d, useAtriumIntegration as f, useAtriumScreen as p } from "@refactor-circus/atrium";
import { UAlert as m, UBadge as h, UButton as ae, UCard as oe, UCheckbox as se, UFormField as ce, UInput as le, USelect as ue, USkeleton as de, UTable as g } from "@refactor-circus/atrium/ui";
import { Fragment as _, computed as v, createBlock as y, createCommentVNode as b, createElementBlock as x, createElementVNode as S, createTextVNode as C, createVNode as w, defineComponent as T, isRef as fe, openBlock as E, reactive as pe, ref as D, renderList as O, shallowRef as k, toDisplayString as A, unref as j, useTemplateRef as me, watch as M, withCtx as N, withModifiers as P } from "vue";
import { useI18n as F } from "vue-i18n";
var I = "showroom:", L = [
	"upsert",
	"create",
	"update"
], R = ["jsonl", "csv"];
function z(e) {
	let t = e?.flow_prefix;
	return typeof t == "string" ? t : I;
}
function he(e, t) {
	return e.filter((e) => e.slug.startsWith(t)).map((e) => e.slug);
}
function B(e) {
	let t = e.created_at ? new Date(e.created_at).getTime() : NaN;
	return Number.isNaN(t) ? 0 : t;
}
function ge(e, t = 20) {
	return e.flat().sort((e, t) => B(t) - B(e)).slice(0, t);
}
function V(e) {
	let t = e.error?.message;
	return typeof t == "string" && t !== "" ? t : null;
}
function H(e) {
	switch (e) {
		case "completed": return "success";
		case "failed": return "error";
		case "running":
		case "pending":
		case "waiting": return "info";
		case "rolling_back": return "warning";
		default: return "neutral";
	}
}
function _e(e) {
	return {
		format: e.format,
		...e.family ? { family: e.family } : {},
		...e.category ? { category: e.category } : {},
		...e.scope ? { scope: e.scope } : {},
		published: e.published
	};
}
// src/components/transfers/TransferRunsTable.vue?vue&type=script&setup=true&lang.ts
var U = ["data-testid"], W = {
	key: 0,
	class: "mt-1 text-xs text-error"
}, G = { class: "text-muted" }, K = { class: "text-muted" }, q = { class: "flex justify-end gap-2" }, ve = /* @__PURE__ */ T({
	__name: "TransferRunsTable",
	props: {
		runs: {},
		rowActions: {}
	},
	setup(e) {
		let t = e, { t: n } = F(), r = v(() => [
			{
				accessorKey: "flow",
				header: n("showroom.transfers.flow")
			},
			{
				accessorKey: "status",
				header: n("showroom.transfers.status")
			},
			{
				accessorKey: "started_at",
				header: n("showroom.transfers.started")
			},
			{
				accessorKey: "finished_at",
				header: n("showroom.transfers.finished")
			},
			...t.rowActions.length > 0 ? [{
				id: "actions",
				header: "",
				meta: { class: { td: "text-right" } }
			}] : []
		]);
		return (e, i) => {
			let o = h, s = l, c = g;
			return E(), y(c, {
				data: t.runs,
				columns: j(r),
				"data-testid": "showroom-transfers-table"
			}, {
				"flow-cell": N(({ row: { original: e } }) => [S("span", {
					class: "font-mono text-xs",
					"data-testid": `showroom-transfer-run-${e.id}`
				}, A(e.flow), 9, U)]),
				"status-cell": N(({ row: { original: e } }) => [w(o, {
					color: j(H)(e.status),
					variant: "subtle"
				}, {
					default: N(() => [C(A(e.status), 1)]),
					_: 2
				}, 1032, ["color"]), j(V)(e) ? (E(), x("p", W, A(j(V)(e)), 1)) : b("", !0)]),
				"started_at-cell": N(({ row: { original: e } }) => [S("span", G, A(j(a)(e.started_at ?? e.created_at) ?? j(n)("showroom.catalog.none")), 1)]),
				"finished_at-cell": N(({ row: { original: e } }) => [S("span", K, A(j(a)(e.finished_at) ?? j(n)("showroom.catalog.none")), 1)]),
				"actions-cell": N(({ row: { original: e } }) => [S("div", q, [(E(!0), x(_, null, O(t.rowActions, (t) => (E(), y(s, {
					key: t.key,
					extension: t,
					record: e.id
				}, null, 8, ["extension", "record"]))), 128))])]),
				_: 1
			}, 8, ["data", "columns"]);
		};
	}
}), ye = {
	class: "space-y-4",
	"data-testid": "showroom-transfers"
}, be = { class: "space-y-4" }, xe = { class: "grid gap-4 lg:grid-cols-2" }, Se = { class: "font-semibold" }, Ce = { class: "font-semibold" }, we = { class: "font-semibold" }, J = /* @__PURE__ */ T({
	__name: "index",
	setup(a) {
		let { t: l, locale: h } = F(), g = s(), _ = f("impex"), T = i(), O = i(), { data: I, error: B } = p("showroom.transfers"), V = v(() => I.value ? z(I.value.data.options) : B.value ? z(null) : null), H = v(() => B.value ? [] : I.value?.data.sections["row.actions"] ?? []), U = k([]), W = D(!1), G = D(null), K = D(!1);
		async function q() {
			let e = _.client, t = V.value;
			if (e && t !== null) try {
				let n = d(await e.GET("/flows")).data, r = await Promise.all(he(n, t).map(async (t) => d(await e.GET("/runs", { params: { query: {
					flow: t,
					per_page: 20
				} } })).data));
				U.value = ge(r), K.value = !1, G.value = null;
			} catch (e) {
				K.value = !0, G.value = u(e);
			} finally {
				W.value = !0;
			}
		}
		M(V, () => void q(), { immediate: !0 });
		let J = t(async (e) => n(await e.GET("/families", { params: { query: { per_page: 100 } } }))), Te = t(c), Ee = t(async (e) => n(await e.GET("/channels", { params: { query: { per_page: 100 } } }))), Y = D("upsert"), X = k(null), Z = me("fileInput"), De = v(() => L.map((e) => ({
			label: l(`showroom.transfers.import.modes.${e}`),
			value: e
		}))), Oe = v(() => R.map((e) => ({
			label: l(`showroom.transfers.export.formats.${e}`),
			value: e
		}))), ke = v(() => [{
			label: l("showroom.transfers.export.all_families"),
			value: null
		}, ...J.value.map((e) => ({
			label: o(e.labels, h.value, e.code),
			value: e.code
		}))]), Ae = v(() => [{
			label: l("showroom.transfers.export.all_categories"),
			value: null
		}, ...Te.value.map((e) => ({
			label: o(e.labels, h.value, e.code),
			value: e.code
		}))]), je = v(() => [{
			label: l("showroom.transfers.export.no_channel"),
			value: null
		}, ...Ee.value.map((e) => ({
			label: o(e.labels, h.value, e.code),
			value: e.code
		}))]), Q = pe({
			format: "jsonl",
			family: "",
			category: "",
			scope: "",
			published: !1
		}), $ = k(null);
		function Me(e) {
			X.value = e.target.files?.[0] ?? null;
		}
		async function Ne() {
			let e = X.value;
			if (!g || !e) return;
			let t = new FormData();
			t.append("file", e), t.append("mode", Y.value);
			let r = { run: null };
			await T.run(async () => {
				r.run = n(await g.POST("/imports", {
					body: t,
					bodySerializer: (e) => e
				})).data;
			}) && ($.value = r.run, X.value = null, Z.value?.inputRef && (Z.value.inputRef.value = ""), await q());
		}
		async function Pe() {
			if (!g) return;
			let e = { run: null };
			await O.run(async () => {
				e.run = n(await g.POST("/exports", { body: r(_e(Q)) })).data;
			}) && ($.value = e.run, await q());
		}
		return (t, n) => {
			let r = re, i = m, a = le, o = ce, s = ue, c = ae, u = oe, d = se, f = ne, p = ee, h = de, g = ie, v = te;
			return E(), x("div", ye, [w(r, { title: j(l)("showroom.transfers.title") }, null, 8, ["title"]), w(v, { "screen-key": "showroom.transfers" }, {
				default: N(() => [S("div", be, [
					j($) ? (E(), y(i, {
						key: 0,
						color: "success",
						variant: "subtle",
						icon: "i-lucide-circle-check",
						title: j(l)("showroom.transfers.started_notice", { flow: j($).flow }),
						"data-testid": "showroom-transfer-started"
					}, null, 8, ["title"])) : b("", !0),
					S("div", xe, [w(u, { "data-testid": "showroom-transfer-import" }, {
						header: N(() => [S("h2", Se, A(j(l)("showroom.transfers.import.title")), 1)]),
						default: N(() => [S("form", {
							class: "flex flex-col gap-3",
							"data-testid": "showroom-transfer-import-form",
							onSubmit: P(Ne, ["prevent"])
						}, [
							w(e, { action: j(T) }, null, 8, ["action"]),
							w(o, {
								label: j(l)("showroom.transfers.import.file"),
								hint: j(l)("showroom.transfers.import.file_hint"),
								error: j(T).error("file"),
								required: ""
							}, {
								default: N(() => [w(a, {
									ref_key: "fileInput",
									ref: Z,
									type: "file",
									accept: ".csv,.jsonl,text/csv",
									class: "w-full",
									"data-testid": "showroom-transfer-import-file",
									onChange: Me
								}, null, 512)]),
								_: 1
							}, 8, [
								"label",
								"hint",
								"error"
							]),
							w(o, {
								label: j(l)("showroom.transfers.import.mode"),
								error: j(T).error("mode")
							}, {
								default: N(() => [w(s, {
									modelValue: j(Y),
									"onUpdate:modelValue": n[0] ||= (e) => fe(Y) ? Y.value = e : null,
									items: j(De),
									class: "w-full",
									"data-testid": "showroom-transfer-import-mode"
								}, null, 8, ["modelValue", "items"])]),
								_: 1
							}, 8, ["label", "error"]),
							S("div", null, [w(c, {
								type: "submit",
								icon: "i-lucide-upload",
								loading: j(T).busy.value,
								"data-testid": "showroom-transfer-import-submit"
							}, {
								default: N(() => [C(A(j(l)("showroom.transfers.import.submit")), 1)]),
								_: 1
							}, 8, ["loading"])])
						], 32)]),
						_: 1
					}), w(u, { "data-testid": "showroom-transfer-export" }, {
						header: N(() => [S("h2", Ce, A(j(l)("showroom.transfers.export.title")), 1)]),
						default: N(() => [S("form", {
							class: "flex flex-col gap-3",
							"data-testid": "showroom-transfer-export-form",
							onSubmit: P(Pe, ["prevent"])
						}, [
							w(e, { action: j(O) }, null, 8, ["action"]),
							w(o, {
								label: j(l)("showroom.transfers.export.format"),
								error: j(O).error("format")
							}, {
								default: N(() => [w(s, {
									modelValue: j(Q).format,
									"onUpdate:modelValue": n[1] ||= (e) => j(Q).format = e,
									items: j(Oe),
									class: "w-full",
									"data-testid": "showroom-transfer-export-format"
								}, null, 8, ["modelValue", "items"])]),
								_: 1
							}, 8, ["label", "error"]),
							w(o, {
								label: j(l)("showroom.catalog.fields.family"),
								error: j(O).error("family")
							}, {
								default: N(() => [w(s, {
									modelValue: j(Q).family,
									"onUpdate:modelValue": n[2] ||= (e) => j(Q).family = e,
									items: j(ke),
									placeholder: j(l)("showroom.transfers.export.all_families"),
									class: "w-full",
									"data-testid": "showroom-transfer-export-family"
								}, null, 8, [
									"modelValue",
									"items",
									"placeholder"
								])]),
								_: 1
							}, 8, ["label", "error"]),
							w(o, {
								label: j(l)("showroom.transfers.export.category_tree"),
								error: j(O).error("category")
							}, {
								default: N(() => [w(s, {
									modelValue: j(Q).category,
									"onUpdate:modelValue": n[3] ||= (e) => j(Q).category = e,
									items: j(Ae),
									placeholder: j(l)("showroom.transfers.export.all_categories"),
									class: "w-full",
									"data-testid": "showroom-transfer-export-category"
								}, null, 8, [
									"modelValue",
									"items",
									"placeholder"
								])]),
								_: 1
							}, 8, ["label", "error"]),
							w(o, {
								label: j(l)("showroom.catalog.fields.channel"),
								error: j(O).error("scope")
							}, {
								default: N(() => [w(s, {
									modelValue: j(Q).scope,
									"onUpdate:modelValue": n[4] ||= (e) => j(Q).scope = e,
									items: j(je),
									placeholder: j(l)("showroom.transfers.export.no_channel"),
									class: "w-full",
									"data-testid": "showroom-transfer-export-scope"
								}, null, 8, [
									"modelValue",
									"items",
									"placeholder"
								])]),
								_: 1
							}, 8, ["label", "error"]),
							w(d, {
								modelValue: j(Q).published,
								"onUpdate:modelValue": n[5] ||= (e) => j(Q).published = e,
								label: j(l)("showroom.transfers.export.published"),
								"data-testid": "showroom-transfer-export-published"
							}, null, 8, ["modelValue", "label"]),
							S("div", null, [w(c, {
								type: "submit",
								icon: "i-lucide-download",
								loading: j(O).busy.value,
								"data-testid": "showroom-transfer-export-submit"
							}, {
								default: N(() => [C(A(j(l)("showroom.transfers.export.submit")), 1)]),
								_: 1
							}, 8, ["loading"])])
						], 32)]),
						_: 1
					})]),
					w(u, { "data-testid": "showroom-transfers-runs" }, {
						header: N(() => [S("h2", we, A(j(l)("showroom.transfers.recent")), 1)]),
						default: N(() => [j(_).active ? j(K) && j(G) === 403 ? (E(), y(f, {
							key: 1,
							compact: ""
						})) : j(K) ? (E(), y(p, {
							key: 2,
							message: j(l)("showroom.transfers.runs_failed")
						}, null, 8, ["message"])) : j(W) ? j(U).length === 0 ? (E(), y(g, {
							key: 4,
							compact: "",
							title: j(l)("showroom.transfers.no_runs"),
							"data-testid": "showroom-transfers-no-runs"
						}, null, 8, ["title"])) : (E(), y(ve, {
							key: 5,
							runs: j(U),
							"row-actions": j(H)
						}, null, 8, ["runs", "row-actions"])) : (E(), y(h, {
							key: 3,
							class: "h-24"
						})) : (E(), y(i, {
							key: 0,
							color: "neutral",
							variant: "subtle",
							icon: "i-lucide-plug",
							title: j(l)("showroom.transfers.requires_impex"),
							description: j(l)("showroom.transfers.requires_impex_hint"),
							"data-testid": "showroom-transfers-requires-impex"
						}, null, 8, ["title", "description"]))]),
						_: 1
					})
				])]),
				_: 1
			})]);
		};
	}
});
export { J as default };
